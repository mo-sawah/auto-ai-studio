#!/usr/bin/env python3

# --- Dependencies ---
# pip install requests beautifulsoup4 newspaper3k ddgs spacy semanticscholar
# python -m spacy download en_core_web_md

import sys
import json
import requests
import time
import re
from datetime import datetime, timezone
from typing import List, Dict, Optional, Any
from bs4 import BeautifulSoup
from ddgs import DDGS
import spacy
from semanticscholar import SemanticScholar

try:
    from urllib.parse import urlparse
except ImportError:
    from urlparse import urlparse

try:
    from newspaper import Article, ArticleException
    NEWSPAPER_AVAILABLE = True
except ImportError:
    NEWSPAPER_AVAILABLE = False

# Load the upgraded spaCy NLP model with word vectors
try:
    NLP = spacy.load("en_core_web_md")
except OSError:
    print("spaCy model 'en_core_web_md' not found.", file=sys.stderr)
    print("Please run: python -m spacy download en_core_web_md", file=sys.stderr)
    NLP = None

class IntelligentContentResearcher:
    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        })
        self.rss_sources = [
            {"name": "Reuters", "url": "https://feeds.reuters.com/reuters/topNews", "credibility": 5.0},
            {"name": "AP News", "url": "https://feeds.apnews.com/rss/apf-topnews", "credibility": 5.0},
            {"name": "BBC News", "url": "https://feeds.bbci.co.uk/news/rss.xml", "credibility": 4.8},
        ]
        self.s2 = SemanticScholar()

    def research_topic(self, topic: str, max_sources: int = 5) -> Dict[str, Any]:
        if not NLP:
            return {"success": False, "error": "spaCy model not loaded. Cannot perform research.", "topic": topic}
        
        try:
            topic_doc = NLP(topic)
            all_sources = []
            
            # Strategy 1: Web Search
            all_sources.extend(self._ddg_web_search(topic_doc, max_sources))
            
            # Strategy 2: Academic Search
            all_sources.extend(self._semantic_scholar_search(topic_doc, 2))
            
            # Strategy 3: RSS Feeds (can be re-enabled if needed)
            # all_sources.extend(self._diverse_rss_search(topic_doc, max_sources))

            diverse_sources = self._ensure_source_diversity(all_sources)
            
            for source in diverse_sources:
                source['final_score'] = self._calculate_final_score(source)
            
            ranked_sources = sorted(diverse_sources, key=lambda x: x['final_score'], reverse=True)
            
            return {
                "success": True, "topic": topic, "sources": ranked_sources[:max_sources],
                "analysis": self._analyze_sources(topic, ranked_sources),
                "timestamp": datetime.now().isoformat()
            }
        except Exception as e:
            return {"success": False, "error": str(e), "topic": topic}

    def _calculate_final_score(self, source: Dict[str, Any]) -> float:
        """Calculates a weighted score based on relevance, recency, and credibility."""
        relevance = source.get('relevance_score', 0) * 0.5  # 50% weight
        recency = source.get('recency_score', 0) * 0.3    # 30% weight
        credibility = (source.get('credibility_score', 2.5) / 5.0) * 0.2  # 20% weight
        return relevance + recency + credibility

    def _get_recency_score(self, pub_date: Optional[datetime]) -> float:
        if not pub_date:
            return 0.5
        if pub_date.tzinfo is None:
            pub_date = pub_date.replace(tzinfo=timezone.utc)
        
        days_old = (datetime.now(timezone.utc) - pub_date).days
        if days_old < 0: return 1.0
        if days_old <= 1: return 1.0
        if days_old > 30: return 0.0
        
        return 1.0 - (days_old / 30.0)

    def _calculate_relevance_score(self, topic_doc: Any, content_doc: Any) -> float:
        if not content_doc or not content_doc.has_vector or content_doc.vector_norm == 0:
            return 0.0
        
        similarity = topic_doc.similarity(content_doc)
        
        topic_ents = {ent.text.lower(): ent.label_ for ent in topic_doc.ents}
        content_ents = {ent.text.lower(): ent.label_ for ent in content_doc.ents}
        
        entity_bonus = 0.0
        for topic_ent, topic_label in topic_ents.items():
            if topic_ent in content_ents and content_ents[topic_ent] == topic_label:
                entity_bonus += 0.2
        
        return min(similarity + entity_bonus, 1.0)
    
    def _process_web_source(self, url: str, topic_doc: Any) -> Optional[Dict[str, Any]]:
        try:
            if not NEWSPAPER_AVAILABLE: return None
            article = Article(url)
            article.download()
            article.parse()

            title = article.title
            content = article.text
            pub_date = article.publish_date

            if not title or len(content.split()) < 100: return None
            
            content_doc = NLP(f"{title}\n{content[:2000]}")
            # Lowered threshold slightly to be less strict
            relevance = self._calculate_relevance_score(topic_doc, content_doc)
            if relevance < 0.55: return None
            
            return {
                'url': url, 'title': title, 'content': content,
                'snippet': content[:400], 'domain': urlparse(url).netloc,
                'published_date': pub_date.isoformat() if pub_date else None,
                'credibility_score': self._assess_credibility(urlparse(url).netloc),
                'relevance_score': relevance,
                'recency_score': self._get_recency_score(pub_date),
                'strategy': 'web_search'
            }
        except (ArticleException, requests.exceptions.RequestException):
            return None

    def _ddg_web_search(self, topic_doc: Any, max_results: int) -> List[Dict[str, Any]]:
        sources = []
        try:
            with DDGS() as ddgs:
                for result in ddgs.text(f'"{topic_doc.text}"', region='wt-wt', max_results=max_results + 5):
                    if len(sources) >= max_results: break
                    url = result.get('href')
                    if url:
                        source = self._process_web_source(url, topic_doc)
                        if source: sources.append(source)
        except Exception as e:
            print(f"DDGS Search Error: {e}", file=sys.stderr)
        return sources
        
    def _semantic_scholar_search(self, topic_doc: Any, max_results: int) -> List[Dict[str, Any]]:
        sources = []
        try:
            results = self.s2.search_paper(query=topic_doc.text, limit=max_results)
            for paper in results:
                pub_date = paper.publicationDate
                dt_object = None
                
                # FIX: Check if pub_date is a string before parsing, otherwise use it directly.
                if isinstance(pub_date, str):
                    dt_object = datetime.strptime(pub_date, '%Y-%m-%d').replace(tzinfo=timezone.utc)
                elif isinstance(pub_date, datetime):
                    dt_object = pub_date.replace(tzinfo=timezone.utc)

                # FIX: Ensure content is not null
                content_text = paper.abstract or ""
                content = f"{paper.title}. {content_text}"
                content_doc = NLP(content)
                relevance = self._calculate_relevance_score(topic_doc, content_doc)
                if relevance > 0.6:
                    sources.append({
                        'url': paper.url, 'title': paper.title, 'content': content_text, 
                        'snippet': content_text[:400], 'domain': 'semanticscholar.org',
                        'published_date': dt_object.isoformat() if dt_object else None,
                        'credibility_score': 4.5,
                        'relevance_score': relevance,
                        'recency_score': self._get_recency_score(dt_object),
                        'strategy': 'academic_search'
                    })
        except Exception as e:
            print(f"Semantic Scholar Error: {e}", file=sys.stderr)
        return sources

    def _ensure_source_diversity(self, sources: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        unique_sources = {}
        for source in sources:
            if source.get('url') and source['url'] not in unique_sources:
                unique_sources[source['url']] = source
        return list(unique_sources.values())

    def _assess_credibility(self, domain: str) -> float:
        domain = domain.lower()
        if any(d in domain for d in ['reuters.com', 'apnews.com', 'bbc.com']): return 5.0
        if any(d in domain for d in ['nytimes.com', 'wsj.com', 'npr.org']): return 4.5
        if 'semanticscholar.org' in domain or '.edu' in domain: return 4.5
        if '.gov' in domain: return 4.8
        return 3.0

    def _analyze_sources(self, topic: str, sources: List[Dict[str, Any]]) -> Dict[str, Any]:
        if not sources: return {"summary": f"No relevant sources found for topic: {topic}"}
        
        all_text = ". ".join(s['content'] for s in sources if s.get('content'))
        doc = NLP(all_text[:100000])
        keywords = [chunk.text for chunk in doc.noun_chunks if len(chunk.text.split()) > 1 and topic.lower() not in chunk.text.lower()]
        
        return {
            "total_sources_found": len(sources),
            "avg_final_score": sum(s['final_score'] for s in sources) / len(sources),
            "domains": list(set(s['domain'] for s in sources)),
            "top_concepts": list(dict.fromkeys(keywords))[:10],
            "summary": f"Found {len(sources)} high-quality sources."
        }


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Topic argument is required."}, indent=2))
        sys.exit(1)
    
    topic = " ".join(sys.argv[1:])
    researcher = IntelligentContentResearcher()
    result = researcher.research_topic(topic, max_sources=5)
    print(json.dumps(result, indent=2, default=str))


if __name__ == "__main__":
    main()