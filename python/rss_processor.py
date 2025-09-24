#!/usr/bin/env python3
"""
Auto AI Studio RSS Processor
Processes RSS feeds and extracts relevant content for AI generation
"""

import sys
import json
import requests
import xml.etree.ElementTree as ET
from datetime import datetime, timedelta
from typing import List, Dict, Optional
import re
import time

class RSSProcessor:
    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (compatible; AutoAIStudio/1.0; +https://sawahsolutions.com)'
        })
        self.session.timeout = 30
    
    def process_feeds(self, feeds: List[Dict], keywords: List[str] = None, max_items: int = 10) -> Dict:
        """Process multiple RSS feeds and filter by keywords"""
        try:
            all_items = []
            feed_stats = {}
            
            for feed in feeds:
                feed_items = self._process_single_feed(feed, keywords, max_items)
                if feed_items['success']:
                    all_items.extend(feed_items['items'])
                    feed_stats[feed['name']] = {
                        'items_found': len(feed_items['items']),
                        'status': 'success'
                    }
                else:
                    feed_stats[feed['name']] = {
                        'items_found': 0,
                        'status': 'error',
                        'error': feed_items.get('error', 'Unknown error')
                    }
            
            # Sort by relevance and date
            if keywords:
                all_items = self._rank_by_relevance(all_items, keywords)
            else:
                all_items = sorted(all_items, key=lambda x: x.get('published', ''), reverse=True)
            
            return {
                'success': True,
                'items': all_items[:max_items],
                'total_feeds': len(feeds),
                'total_items': len(all_items),
                'feed_stats': feed_stats,
                'processed_at': datetime.now().isoformat()
            }
            
        except Exception as e:
            return {
                'success': False,
                'error': str(e)
            }
    
    def _process_single_feed(self, feed: Dict, keywords: List[str] = None, max_items: int = 10) -> Dict:
        """Process a single RSS feed"""
        try:
            response = self.session.get(feed['url'])
            response.raise_for_status()
            
            # Parse XML
            root = ET.fromstring(response.content)
            
            items = []
            
            # Handle RSS 2.0
            if root.find('.//item') is not None:
                items = self._parse_rss_items(root, feed, keywords, max_items)
            # Handle Atom
            elif root.find('.//{http://www.w3.org/2005/Atom}entry') is not None:
                items = self._parse_atom_items(root, feed, keywords, max_items)
            
            return {
                'success': True,
                'items': items,
                'feed_name': feed['name']
            }
            
        except Exception as e:
            return {
                'success': False,
                'error': f"Error processing feed {feed['name']}: {str(e)}"
            }
    
    def _parse_rss_items(self, root: ET.Element, feed: Dict, keywords: List[str], max_items: int) -> List[Dict]:
        """Parse RSS 2.0 format items"""
        items = []
        
        for item in root.findall('.//item')[:max_items * 2]:  # Get extra in case we filter some out
            try:
                title = self._get_text(item.find('title'))
                description = self._get_text(item.find('description'))
                link = self._get_text(item.find('link'))
                pub_date = self._get_text(item.find('pubDate'))
                author = self._get_text(item.find('author')) or self._get_text(item.find('{http://purl.org/dc/elements/1.1/}creator'))
                
                if not title or not link:
                    continue
                
                # Clean up description (remove HTML tags)
                description = self._clean_html(description) if description else ''
                
                # Check keyword relevance if keywords provided
                if keywords and not self._matches_keywords(title + ' ' + description, keywords):
                    continue
                
                # Extract content if available
                content_encoded = self._get_text(item.find('{http://purl.org/rss/1.0/modules/content/}encoded'))
                full_content = self._clean_html(content_encoded) if content_encoded else description
                
                item_data = {
                    'title': title,
                    'link': link,
                    'description': description[:500],  # Limit description length
                    'content': full_content[:2000],  # Limit content length
                    'published': self._parse_date(pub_date),
                    'author': author,
                    'source': feed['name'],
                    'source_url': feed['url'],
                    'category': feed.get('category', ''),
                    'word_count': len(full_content.split()) if full_content else 0,
                    'relevance_score': self._calculate_relevance(title + ' ' + description, keywords) if keywords else 1.0
                }
                
                items.append(item_data)
                
                if len(items) >= max_items:
                    break
                    
            except Exception as e:
                continue
        
        return items
    
    def _parse_atom_items(self, root: ET.Element, feed: Dict, keywords: List[str], max_items: int) -> List[Dict]:
        """Parse Atom format items"""
        items = []
        atom_ns = '{http://www.w3.org/2005/Atom}'
        
        for entry in root.findall(f'.//{atom_ns}entry')[:max_items * 2]:
            try:
                title = self._get_text(entry.find(f'{atom_ns}title'))
                summary = self._get_text(entry.find(f'{atom_ns}summary'))
                content = self._get_text(entry.find(f'{atom_ns}content'))
                
                # Get link
                link_elem = entry.find(f'{atom_ns}link[@rel="alternate"]')
                if link_elem is None:
                    link_elem = entry.find(f'{atom_ns}link')
                link = link_elem.get('href') if link_elem is not None else ''
                
                published = self._get_text(entry.find(f'{atom_ns}published')) or self._get_text(entry.find(f'{atom_ns}updated'))
                author_elem = entry.find(f'{atom_ns}author/{atom_ns}name')
                author = self._get_text(author_elem) if author_elem is not None else ''
                
                if not title or not link:
                    continue
                
                # Use content if available, otherwise summary
                description = self._clean_html(content or summary or '')
                
                # Check keyword relevance
                if keywords and not self._matches_keywords(title + ' ' + description, keywords):
                    continue
                
                item_data = {
                    'title': title,
                    'link': link,
                    'description': description[:500],
                    'content': description[:2000],
                    'published': self._parse_date(published),
                    'author': author,
                    'source': feed['name'],
                    'source_url': feed['url'],
                    'category': feed.get('category', ''),
                    'word_count': len(description.split()) if description else 0,
                    'relevance_score': self._calculate_relevance(title + ' ' + description, keywords) if keywords else 1.0
                }
                
                items.append(item_data)
                
                if len(items) >= max_items:
                    break
                    
            except Exception as e:
                continue
        
        return items
    
    def _get_text(self, element) -> str:
        """Safely get text content from XML element"""
        if element is not None:
            return element.text or ''
        return ''
    
    def _clean_html(self, html_text: str) -> str:
        """Remove HTML tags and clean up text"""
        if not html_text:
            return ''
        
        # Remove HTML tags
        clean = re.sub(r'<[^>]+>', '', html_text)
        
        # Clean up whitespace
        clean = re.sub(r'\s+', ' ', clean).strip()
        
        # Remove common HTML entities
        clean = clean.replace('&amp;', '&')
        clean = clean.replace('&lt;', '<')
        clean = clean.replace('&gt;', '>')
        clean = clean.replace('&quot;', '"')
        clean = clean.replace('&#39;', "'")
        clean = clean.replace('&nbsp;', ' ')
        
        return clean
    
    def _parse_date(self, date_str: str) -> str:
        """Parse various date formats to ISO format"""
        if not date_str:
            return datetime.now().isoformat()
        
        # Common date formats in RSS/Atom
        formats = [
            '%a, %d %b %Y %H:%M:%S %z',  # RFC 2822
            '%a, %d %b %Y %H:%M:%S %Z',
            '%Y-%m-%dT%H:%M:%S%z',       # ISO 8601
            '%Y-%m-%dT%H:%M:%SZ',
            '%Y-%m-%d %H:%M:%S',
            '%Y-%m-%d'
        ]
        
        for fmt in formats:
            try:
                parsed = datetime.strptime(date_str.strip(), fmt)
                return parsed.isoformat()
            except ValueError:
                continue
        
        # If no format matches, return current time
        return datetime.now().isoformat()
    
    def _matches_keywords(self, text: str, keywords: List[str]) -> bool:
        """Check if text matches any of the keywords"""
        text_lower = text.lower()
        
        for keyword in keywords:
            if keyword.lower().strip() in text_lower:
                return True
        
        return False
    
    def _calculate_relevance(self, text: str, keywords: List[str]) -> float:
        """Calculate relevance score based on keyword matches"""
        if not keywords:
            return 1.0
        
        text_lower = text.lower()
        total_score = 0.0
        
        for keyword in keywords:
            keyword = keyword.lower().strip()
            if keyword in text_lower:
                # Count occurrences
                count = text_lower.count(keyword)
                # Weight by keyword length (longer keywords get higher scores)
                weight = len(keyword.split())
                total_score += count * weight
        
        # Normalize by text length
        return min(total_score / len(text.split()) * 100, 10.0)
    
    def _rank_by_relevance(self, items: List[Dict], keywords: List[str]) -> List[Dict]:
        """Rank items by relevance and recency"""
        def score_item(item):
            relevance = item.get('relevance_score', 0)
            
            # Add recency bonus (items from last 24 hours get bonus)
            try:
                pub_date = datetime.fromisoformat(item['published'].replace('Z', '+00:00'))
                hours_old = (datetime.now() - pub_date.replace(tzinfo=None)).total_seconds() / 3600
                recency_bonus = max(0, (24 - hours_old) / 24) * 2  # Up to 2 point bonus
            except:
                recency_bonus = 0
            
            return relevance + recency_bonus
        
        return sorted(items, key=score_item, reverse=True)

def main():
    """Main function for command line usage"""
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Feed configuration required"}))
        sys.exit(1)
    
    try:
        # Parse arguments
        config = json.loads(sys.argv[1])
        feeds = config.get('feeds', [])
        keywords = config.get('keywords', [])
        max_items = config.get('max_items', 10)
        
        processor = RSSProcessor()
        result = processor.process_feeds(feeds, keywords, max_items)
        
        print(json.dumps(result, indent=2))
        
    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    main()