#!/usr/bin/env python3

import sys
import json
import requests
import re
from datetime import datetime


class AutoAIContentResearcher:
    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        })
    
    def research_topic(self, topic, max_sources=5):
        try:
            # Use RSS feeds from major news sources
            sources = []
            
            # Reliable RSS feeds
            feeds = [
                ("Reuters", "https://feeds.reuters.com/reuters/topNews"),
                ("BBC", "https://feeds.bbci.co.uk/news/rss.xml"),
                ("AP News", "https://feeds.apnews.com/rss/apf-topnews"),
            ]
            
            for name, url in feeds:
                try:
                    items = self._get_rss_items(name, url, topic)
                    sources.extend(items[:2])  # Get top 2 from each
                except:
                    continue
            
            # Filter for relevance
            relevant_sources = []
            topic_lower = topic.lower()
            
            for source in sources:
                content = (source['title'] + ' ' + source['content']).lower()
                if any(word in content for word in topic_lower.split()):
                    relevant_sources.append(source)
            
            # Sort by credibility
            relevant_sources.sort(key=lambda x: x['credibility_score'], reverse=True)
            
            analysis = {
                "total_sources": len(relevant_sources),
                "avg_credibility": sum(s['credibility_score'] for s in relevant_sources) / len(relevant_sources) if relevant_sources else 0,
                "summary": f"Found {len(relevant_sources)} sources about {topic}"
            }
            
            return {
                "success": True,
                "topic": topic,
                "sources": relevant_sources[:max_sources],
                "analysis": analysis,
                "timestamp": datetime.now().isoformat()
            }
            
        except Exception as e:
            return {
                "success": False,
                "error": str(e),
                "topic": topic
            }
    
    def _get_rss_items(self, name, url, topic):
        response = self.session.get(url, timeout=15)
        if response.status_code != 200:
            return []
        
        items = []
        content = response.text
        
        # Extract items using simple regex
        item_matches = re.findall(r'<item[^>]*>(.*?)</item>', content, re.DOTALL | re.IGNORECASE)
        
        for item in item_matches[:5]:
            title = self._extract_tag(item, 'title')
            link = self._extract_tag(item, 'link')
            desc = self._extract_tag(item, 'description')
            
            if title and link:
                # Clean HTML
                desc_clean = re.sub(r'<[^>]+>', '', desc)
                desc_clean = re.sub(r'\s+', ' ', desc_clean).strip()
                
                # Assign credibility based on source
                credibility = 5.0 if 'reuters' in url.lower() or 'ap' in url.lower() else 4.5
                
                items.append({
                    'title': title,
                    'url': link,
                    'content': desc_clean[:1500],
                    'snippet': desc_clean[:200],
                    'domain': name.lower(),
                    'credibility_score': credibility,
                    'source': name,
                    'word_count': len(desc_clean.split()),
                    'extracted_at': datetime.now().isoformat()
                })
        
        return items
    
    def _extract_tag(self, content, tag):
        pattern = f'<{tag}[^>]*>(.*?)</{tag}>'
        match = re.search(pattern, content, re.DOTALL | re.IGNORECASE)
        if match:
            text = match.group(1).strip()
            # Clean HTML entities
            text = text.replace('&amp;', '&').replace('&lt;', '<').replace('&gt;', '>')
            text = text.replace('&quot;', '"').replace('&#39;', "'").replace('&nbsp;', ' ')
            return text
        return ""


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Topic argument required"}))
        sys.exit(1)
    
    topic = sys.argv[1]
    max_sources = int(sys.argv[2]) if len(sys.argv) > 2 else 5
    
    researcher = AutoAIContentResearcher()
    result = researcher.research_topic(topic, max_sources)
    
    print(json.dumps(result, indent=2))


if __name__ == "__main__":
    main()