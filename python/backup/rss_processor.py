#!/usr/bin/env python3
"""
Auto AI Studio RSS Processor
Processes RSS feeds and extracts relevant content for AI generation
"""

import sys
import json
import requests
import re
from datetime import datetime
from typing import List, Dict, Optional

try:
    import xml.etree.ElementTree as ET
except ImportError:
    print(json.dumps({"error": "XML parsing not available"}))
    sys.exit(1)

try:
    import feedparser
except ImportError:
    print(json.dumps({"error": "Missing feedparser. Run: pip install feedparser"}))
    sys.exit(1)


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
        """Process a single RSS feed using feedparser"""
        try:
            response = self.session.get(feed['url'])
            response.raise_for_status()
            
            # Parse with feedparser for better compatibility
            parsed_feed = feedparser.parse(response.content)
            
            items = []
            
            for entry in parsed_feed.entries[:max_items * 2]:
                try:
                    title = getattr(entry, 'title', '')
                    description = getattr(entry, 'description', '') or getattr(entry, 'summary', '')
                    link = getattr(entry, 'link', '')
                    
                    if not title or not link:
                        continue
                    
                    # Clean up description
                    description = self._clean_html(description)
                    
                    # Check keyword relevance if keywords provided
                    if keywords and not self._matches_keywords(title + ' ' + description, keywords):
                        continue
                    
                    # Get published date
                    published = ''
                    if hasattr(entry, 'published_parsed') and entry.published_parsed:
                        try:
                            published = datetime(*entry.published_parsed[:6]).isoformat()
                        except:
                            published = datetime.now().isoformat()
                    else:
                        published = datetime.now().isoformat()
                    
                    # Get author
                    author = getattr(entry, 'author', '') or ''
                    
                    # Get content
                    content = description
                    if hasattr(entry, 'content') and entry.content:
                        try:
                            content = self._clean_html(entry.content[0].value)
                        except:
                            pass
                    
                    item_data = {
                        'title': title,
                        'link': link,
                        'description': description[:500],
                        'content': content[:2000],
                        'published': published,
                        'author': author,
                        'source': feed['name'],
                        'source_url': feed['url'],
                        'category': feed.get('category', ''),
                        'word_count': len(content.split()) if content else 0,
                        'relevance_score': self._calculate_relevance(title + ' ' + description, keywords) if keywords else 1.0
                    }
                    
                    items.append(item_data)
                    
                    if len(items) >= max_items:
                        break
                        
                except Exception:
                    continue
            
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
    
    def _clean_html(self, html_text: str) -> str:
        """Remove HTML tags and clean up text"""
        if not html_text:
            return ''
        
        # Remove HTML tags
        clean = re.sub(r'<[^>]+>', '', html_text)
        
        # Clean up whitespace
        clean = re.sub(r'\s+', ' ', clean).strip()
        
        # Remove common HTML entities
        entities = {
            '&amp;': '&',
            '&lt;': '<',
            '&gt;': '>',
            '&quot;': '"',
            '&#39;': "'",
            '&nbsp;': ' '
        }
        
        for entity, replacement in entities.items():
            clean = clean.replace(entity, replacement)
        
        return clean
    
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
                count = text_lower.count(keyword)
                weight = len(keyword.split())
                total_score += count * weight
        
        # Normalize by text length
        if len(text.split()) > 0:
            return min(total_score / len(text.split()) * 100, 10.0)
        return 0.0
    
    def _rank_by_relevance(self, items: List[Dict], keywords: List[str]) -> List[Dict]:
        """Rank items by relevance and recency"""
        def score_item(item):
            relevance = item.get('relevance_score', 0)
            
            # Add recency bonus
            try:
                pub_date = datetime.fromisoformat(item['published'].replace('Z', '+00:00'))
                hours_old = (datetime.now() - pub_date.replace(tzinfo=None)).total_seconds() / 3600
                recency_bonus = max(0, (24 - hours_old) / 24) * 2
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