#!/usr/bin/env python3
"""
Auto AI Studio Content Researcher
Integrates with your existing human_reporter_ai_system.py approach
"""

import sys
import json
import requests
import time
from datetime import datetime, timedelta
from typing import List, Dict, Optional
import urllib.parse

try:
    from duckduckgo_search import DDGS
    from trafilatura import extract, fetch_url
except ImportError:
    print(json.dumps({"error": "Missing dependencies. Run: pip install duckduckgo-search trafilatura"}))
    sys.exit(1)

class AutoAIContentResearcher:
    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        })
    
    def research_topic(self, topic: str, max_sources: int = 5) -> Dict:
        """Main research function that follows your existing approach"""
        try:
            sources = self._gather_sources(topic, max_sources)
            analysis = self._analyze_sources(topic, sources)
            
            return {
                "success": True,
                "topic": topic,
                "sources": sources,
                "analysis": analysis,
                "timestamp": datetime.now().isoformat()
            }
        except Exception as e:
            return {
                "success": False,
                "error": str(e),
                "topic": topic
            }
    
    def _gather_sources(self, topic: str, max_results: int) -> List[Dict]:
        """Gather sources using multiple search strategies"""
        all_sources = []
        
        # Search strategies similar to your existing approach
        search_strategies = {
            'recent_news': f'{topic} latest news "2024" OR "2025"',
            'authoritative': f'{topic} site:reuters.com OR site:ap.org OR site:bbc.com',
            'expert_analysis': f'{topic} expert analysis research study',
            'comprehensive': f'{topic} complete guide comprehensive'
        }
        
        for strategy, query in search_strategies.items():
            try:
                results = self._search_web(query, max_results // len(search_strategies))
                for result in results:
                    source = self._process_source(result, strategy)
                    if source:
                        all_sources.append(source)
                        
                # Rate limiting
                time.sleep(1)
            except Exception as e:
                continue
        
        # Remove duplicates and rank by credibility
        unique_sources = self._deduplicate_sources(all_sources)
        return sorted(unique_sources, key=lambda x: x['credibility_score'], reverse=True)[:max_results]
    
    def _search_web(self, query: str, max_results: int = 3) -> List[Dict]:
        """Web search using DuckDuckGo"""
        try:
            with DDGS() as ddgs:
                results = list(ddgs.text(query, max_results=max_results))
                return results
        except Exception:
            return []
    
    def _process_source(self, result: Dict, strategy: str) -> Optional[Dict]:
        """Process individual search result"""
        url = result.get('href', result.get('url', ''))
        title = result.get('title', '')
        snippet = result.get('body', result.get('snippet', ''))
        
        if not url or not title:
            return None
        
        # Extract full content
        full_content = self._extract_content(url)
        content = full_content if full_content else snippet
        
        if len(content) < 100:
            return None
        
        # Assess credibility
        credibility = self._assess_credibility(url, title, content)
        
        return {
            'url': url,
            'title': title,
            'content': content[:2000],  # Limit content length
            'snippet': snippet,
            'domain': urllib.parse.urlparse(url).netloc,
            'credibility_score': credibility,
            'strategy': strategy,
            'word_count': len(content.split()),
            'extracted_at': datetime.now().isoformat()
        }
    
    def _extract_content(self, url: str) -> str:
        """Extract full content from URL"""
        try:
            downloaded = fetch_url(url)
            if downloaded:
                content = extract(downloaded)
                if content and len(content) > 200:
                    return content
        except Exception:
            pass
        return ""
    
    def _assess_credibility(self, url: str, title: str, content: str) -> float:
        """Assess source credibility (based on your existing system)"""
        score = 0.0
        domain = url.lower()
        
        # Tier 1: Highest credibility
        tier1_domains = ['reuters.com', 'ap.org', 'bbc.com', 'bloomberg.com']
        if any(d in domain for d in tier1_domains):
            score += 5.0
        
        # Tier 2: High credibility
        tier2_domains = ['cnn.com', 'nytimes.com', 'wsj.com', 'washingtonpost.com', 
                        'guardian.com', 'ft.com', 'economist.com']
        elif any(d in domain for d in tier2_domains):
            score += 4.0
        
        # Government/Academic sources
        elif any(ext in domain for ext in ['.gov', '.edu', '.org']):
            score += 4.5
        
        # General news sites
        elif 'news' in domain or 'times' in domain:
            score += 2.5
        
        else:
            score += 1.5
        
        # Content quality indicators
        if len(content) > 1000:
            score += 0.5
        if content.count('"') > 4:  # Has quotes
            score += 0.5
        if any(phrase in content.lower() for phrase in ['according to', 'sources say', 'study shows']):
            score += 0.5
        
        return min(score, 5.0)
    
    def _deduplicate_sources(self, sources: List[Dict]) -> List[Dict]:
        """Remove duplicate sources"""
        seen_urls = set()
        seen_titles = set()
        unique_sources = []
        
        for source in sources:
            url = source['url']
            title_start = source['title'][:50].lower()
            
            if url not in seen_urls and title_start not in seen_titles:
                seen_urls.add(url)
                seen_titles.add(title_start)
                unique_sources.append(source)
        
        return unique_sources
    
    def _analyze_sources(self, topic: str, sources: List[Dict]) -> Dict:
        """Analyze collected sources for key insights"""
        if not sources:
            return {"summary": f"No sources found for topic: {topic}"}
        
        # Extract key themes
        all_content = ' '.join([s['content'] for s in sources])
        word_freq = {}
        words = all_content.lower().split()
        
        # Simple keyword frequency analysis
        for word in words:
            if len(word) > 4 and word.isalpha():
                word_freq[word] = word_freq.get(word, 0) + 1
        
        top_keywords = sorted(word_freq.items(), key=lambda x: x[1], reverse=True)[:10]
        
        return {
            "total_sources": len(sources),
            "avg_credibility": sum(s['credibility_score'] for s in sources) / len(sources),
            "domains": list(set(s['domain'] for s in sources)),
            "top_keywords": [kw[0] for kw in top_keywords],
            "total_word_count": sum(s['word_count'] for s in sources),
            "summary": f"Found {len(sources)} sources about {topic} with average credibility {sum(s['credibility_score'] for s in sources) / len(sources):.2f}/5.0"
        }

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