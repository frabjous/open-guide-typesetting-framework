// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////// csl.js //////////////////////////////////////
// a list of information about csl json properties and types //
///////////////////////////////////////////////////////////////

export default {
    "types": [
        "article",
        "article-magazine",
        "article-newspaper",
        "bill",
        "broadcast",
        "classic",
        "collection",
        "dataset",
        "document",
        "entry",
        "entry-dictionary",
        "entry-encyclopedia",
        "event",
        "figure",
        "graphic",
        "hearing",
        "interview",
        "legal_case",
        "legislation",
        "manuscript",
        "map",
        "motion_picture",
        "musical_score",
        "pamphlet",
        "paper-conference",
        "patent",
        "performance",
        "periodical",
        "personal_communication",
        "post",
        "post-weblog",
        "regulation",
        "report",
        "review-book",
        "software",
        "song",
        "speech",
        "standard",
        "treaty"
    ],
    "properties": {
        "abstract": "string",
        "accessed": "dateparts",
        "annote": "string",
        "archive_collection": "string",
        "archive_location": "string",
        "archive-place": "string",
        "archive": "string",
        "authority": "string",
        "author": "names",
        "available-date": "date",
        "call-number": "string",
        "chair": "names",
        "chapter-number": "number",
        "citation-key": "string",
        "citation-label": "string",
        "citation-number": "number",
        "collection-editor": "names",
        "collection-number": "number",
        "collection-title": "string",
        "compiler": "names",
        "composer": "names",
        "container-author": "names",
        "container-title-short": "string",
        "container-title": "string",
        "contributor": "names",
        "curator": "names",
        "dimensions": "string",
        "director": "names",
        "division": "string",
        "DOI": "string",
        "edition": "number",
        "editorial-director": "names",
        "editor": "names",
        "event-date": "dateparts",
        "event-place": "string",
        "event-title": "string",
        "executive-producer": "names",
        "first-reference-note-number": "number",
        "genre": "string",
        "guest": "names",
        "host": "names",
        "illustrator": "names",
        "interviewer": "names",
        "ISBN": "string",
        "ISSN": "string",
        "issued": "date",
        "issue": "number",
        "journalAbbreviation": "string",
        "jurisdiction": "string",
        "keyword": "string",
        "language": "string",
        "locator": "number",
        "medium": "string",
        "narrator": "names",
        "note": "string",
        "number": "number",
        "number-of-pages": "number",
        "number-of-volumes": "number",
        "organizer": "names",
        "original-author": "names",
        "original-date": "date",
        "original-publisher-place": "string",
        "original-publisher": "string",
        "original-title": "string",
        "page-first": "number",
        "page": "number",
        "part": "number",
        "part-title": "string",
        "performer": "names",
        "PMCID": "string",
        "PMID": "string",
        "printing": "number",
        "producer": "names",
        "publisher-place": "string",
        "publisher": "string",
        "recipient": "names",
        "references": "string",
        "reviewed-author": "names",
        "reviewed-genre": "string",
        "reviewed-title": "string",
        "scale": "string",
        "script-writer": "names",
        "section": "string",
        "series-creator": "names",
        "shortTitle": "string",
        "source": "string",
        "status": "string",
        "submitted": "dateparts",
        "supplement": "number",
        "title-short": "string",
        "title": "string",
        "translator": "names",
        "URL": "string",
        "version": "string",
        "volume": "number",
        "volume-title-short": "string",
        "volume-title": "string",
        "year-suffix": "string"
    },
    "common": {
        "article-journal": ["author","title","container-title","volume","page","issued"],
        "book": ["author","title","publisher-place","publisher","issued"],
        "chapter": ["author","title","editor","container-title","publisher-place","publisher","page","issued"],
        "review": ["author","reviewed-title","reviewed-author","container-title","volume","page","issued"],
        "thesis": ["author","title","genre","publisher","issued"],
        "webpage": ["author","title","container-title","URL","issued","accessed"]
    }
}
