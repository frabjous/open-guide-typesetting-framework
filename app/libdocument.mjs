// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: libdocument.mjs
// functions for working with markdown documents

export function applyAllBibdata(markdown, bibdata) {
  for (const keyid in bibdata) {
    const bibitem = bibdata[keyid];
    let id = keyid;
    // keyid should be same as id in bibitem, but just in case,
    // we grab it if available
    if (bibitem?.id) id = bibitem.id;
    // replace abbreviations with links
    if (bibitem?.abbreviation) {
      const re = new RegExp('([^\\[])\\*' +
        bibitem.abbreviation + '\\*', 'g');
      const repl = "$1[*" + bibitem.abbreviation + "*](#ref-" +
        id + ')';
      markdown = markdown.replace(re, repl);
    }
    markdown = applyBibitem(markdown, bibitem);
  }
  return markdown;
}

function applyBibitem(markdown, bibitem) {
  // nothing to do if no id
  if (!bibitem?.id) return markdown;
  const id = bibitem.id;
  let namestr = 'Anonymous';
  if (bibitem?.author) namestr = joinNames(bibitem.author);
  if ((namestr == '' || namestr == 'Anonymous') && bibitem?.editor) {
    namestr = joinNames(bibitem.editor);
  }
  let year = 'forthcoming';
  if (bibitem?.issued?.["date-parts"]) {
    const dp = bibitem.issued["date-parts"];
    if (dp?.length > 0 && dp[0]?.length > 0) {
      year = dp[0][0].toString();
    }
    // check for end year
    if (dp?.length > 1 && dp[1]?.length > 0) {
      year += '[-–—]+' + dp[1][0].toString();
    }
  }
  // handle things of the form "(...Russell 1905...)"
  const citepregex = new RegExp(
    '[\\(\\[]([^\\(\\)\[\\]]*)' + namestr + ' ' + year +
    '([^\\(\\)\[\\]]*)[\\)\\]]', 'g'
  );
  const citeprepl = "[$1@" + id + "$2]";
  markdown = markdown.replace(citepregex, citeprepl);
  // handle things of the form "Russell (1905)"
  const citetregex = new RegExp(
    '\\b' + namestr + ' \\(' + year + '\\)', 'g'
  );
  const citetrepl = '@' + id;
  markdown = markdown.replace(citetregex, citetrepl);
  // handle things of the form Russell (1905, p. 10)
  const citetpregex = new RegExp('\\b' + namestr + ' \\(' + year +
    '[, ]*([^\\]\\)]+)\\)', 'g');
  const citetprepl = '@' + id + '[$1]';
  markdown = markdown.replace(citetpregex, citetprepl);
  // ensure there are semicolons between citations
  let old = '';
  while (old != markdown) {
    old = markdown;
    const fixregex = new RegExp(
      '\\[([^@\\[\\]]*)@([^@\\[\\];]*),([^@\[\\];,]*)@([^\[\\]]*)\\]', 'g'
    );
    const fixrepl = "[$1@$2;$3@$4]";
    markdown = markdown.replace(fixregex, fixrepl);
  }
  return markdown;
}

export function extractBibliography(markdown) {
  const lines = markdown.split('\n');
  let ln = lines.length;
  while (ln > 0) {
    ln--;
    const line = lines[ln];
    const condensed = line.toLowerCase().replace(/[^a-z]/g, '');
    if (['bibliography', 'workscited', 'references', 'thebibliography']
      .includes(condensed)) {
      const bibarray = lines.slice(ln+1).filter((l) => (/[A-Z]/.test(l)));
      let savedname = '';
      for (let i=0; i<bibarray.length; i++) {
        const l = bibarray[i];
        let s = l
          // remove asterisks
          .replace(/\*/g,'')
          // remove double quotes
          //.replace(/["“”]/g, '')
          // remove escaped hyphens
          .replace(/\\\-/g, '')
          // remove leading hyphens
          .replace(/^-+\s*/g, '')
          // remove leading punctuation
          .replace(/^[ ,\.]*/g, '')
          // remove escaped single quotes
          .replace(/\\\'/g, "'")
          // remove escaped double quotes
          .replace(/\\\"/g, '"')
          // remove escaped brackets
          .replace(/\\\[/g,'[')
          .replace(/\\\]/g,']');
        // add name from previous check if starts with numeral
        if (/^[0-9]/.test(s) || /^\([0-9]/.test(s)) {
          if (savedname != '') s = savedname + ' ' + s;
        } else {
          // otherwise save the name for next pass
          savedname = s.replace(/[0-9].*/,'').trim();
        }
        bibarray[i] = s;
      }
      return [
        lines.slice(0, (ln > 0) ? (ln - 1) : 0).join('\n'),
        bibarray.join('\n')
      ];
    }
  }
  return [markdown, ''];
}

export function fixMarkdown(
  markdown,
  metadata,
  importreplacements,
  splitsentences = false) {
  const lines = markdown.split('\n');
  let outcome = '';
  let foundAcknowledgements = false;
  for (let ln = 0; ln < lines.length; ln++) {
    let line = lines[ln];
    for (const regexstr in importreplacements) {
      let repl = importreplacements[regexstr];
      // change backrefs to js style
      for (let j=1; j<10; j++) {
        const torepl = '\\' + j.toString();
        const replace = '$' + j.toString();
        repl = repl.replaceAll(torepl, replace);
      }
      const regex = new RegExp(regexstr, 'g');
      line = line.replace(regex, repl, line)
    }
    // look for title, abstract, author, affiliation in first few lines
    // remove them (by skipping them with continue) if found
    let lsq = squish(line);
    if (lsq.startsWith('by')) lsq = lsq.substring(2);
    if (line != '' && (ln < 6 || (ln + 6) > lines.length)) {
      if (/^\**Abstract/.test(line)) continue;
      if (metadata?.title && squish(line) == squish(metadata.title)) {
        continue;
      }
      if (metadata?.author &&
         (squish(joinAuthors(metadata.author, false, false)) == lsq ||
         squish(joinAuthors(metadata.author, false, true)) == lsq ||
         squish(joinAuthors(metadata.author, true, false)) == lsq ||
         squish(joinAuthors(metadata.author, true, true)) == lsq)
      ) {
        continue;
      }
      if ((metadata?.author?.[0]?.email == lsq) ||
        (metadata?.author?.[0]?.affiliation == lsq)) {
        continue;
      }
    }
    if (lsq == 'acknowledgements' || lsq == 'thanks') {
      foundAcknowledgements = true;
      // add blank line before if need be
      if (ln > 0 && lines[ln - 1] != '') {
        outcome += '\n';
      }
      outcome += '## Acknowledgements {.unnumbered}\n';
      // add a blank line after if need be
      if (ln < (lines.length - 1) && lines[ln + 1] != '') {
        outcome += '\n'
      }
      continue;
    }
    if (lsq.startsWith('acknowledgements')) {
      foundAcknowledgements = true;
      const fixedline = line.replace(/^\**acknowledgements[^a-z]*/i,'');
      if (ln > 0 && lines[ln - 1] != '') {
        outcome += '\n';
      }
      outcome += '## Acknowledgements {.unnumbered}\n\n';
      outcome += fixedline + '\n';
      continue;
    }

    // pseudo sections/subsections by lazy people; i.e. bold
    // things without periods
    if (/^\*\*\s*[0-9]+\.\s*[^\.]+[^\s]\s*\*\*$/.test(line)) {
      let fixedline = line.replace(
        /^\*\*\s*[0-9]+\.\s*([^\.]+[^\s])\s*\*\*$/, "# $1"
      );
      // add blank lines before or after
      if (ln > 0 && lines[ln - 1] != '') {
        outcome += '\n';
      }
      outcome += fixedline + '\n';
      if (ln < (lines.length - 1) && lines[ln + 1] != '') {
        outcome += '\n'
      }
      continue;
    }
    // pseudo subsections with N.N. Title
    if (/^\*\*\s*[0-9]+\.[0-9]+\.?\s*[^\.]+[^\s]\s*\*\*$/.test(line)) {
      let fixedline = line.replace(
        /^\*\*\s*[0-9]+\.[0-9]+\.?\s*([^\.]+[^\s])\s*\*\*$/, "## $1"
      );
      // add blank lines before or after
      if (ln > 0 && lines[ln - 1] != '') {
        outcome += '\n';
      }
      outcome += fixedline + '\n';
      if (ln < (lines.length - 1) && lines[ln + 1] != '') {
        outcome += '\n'
      }
      continue;
    }
    // the same but with italics for subsections?
    if (/^\*\s*[0-9]+\.\s*[^\.]+[^\s]\s*\*$/.test(line)) {
      const fixedline = line.replace(
        /^\*\s*[0-9]+\.\s*([^\.]+[^\s])\s*\*$/, "## $1"
      );
      if (ln > 0 && lines[ln - 1] != '') {
        outcome += '\n';
      }
      outcome += fixedline + '\n';
      if (ln < (lines.length - 1) && lines[ln + 1] != '') {
        outcome += '\n'
      }
      continue;
    }
    if (/^\*\s*[0-9]+\.[0-9]+\.?\s*[^\.]+[^\s]\s*\*$/.test(line)) {
      const fixedline = line.replace(
        /^\*\s*[0-9]+\.[0-9]+\.?\s*([^\.]+[^\s])\s*\*$/, "## $1"
      );
      if (ln > 0 && lines[ln - 1] != '') {
        outcome += '\n';
      }
      outcome += fixedline + '\n';
      if (ln < (lines.length - 1) && lines[ln + 1] != '') {
        outcome += '\n'
      }
      continue;
    }
    // pad around headers
    if (/^#/.test(line)) {
      if (ln > 0 && lines[ln - 1] != '') {
        outcome += '\n';
      }
      outcome += line + '\n';
      if (ln < (lines.length - 1) && lines[ln + 1] != '') {
        outcome += '\n'
      }
      continue;
    }
    // split regular paragraphs
    if (splitsentences && /^[\(]?[A-Z]/.test(line)) {
      outcome += splitIntoSentences(line) + '\n';
      continue;
    }
    // normal line just gets added
    outcome += line + '\n'
  }
  if (!foundAcknowledgements) {
    // add blank line before if need be
    if (lines[lines.length - 1] != '') {
      outcome += '\n';
    }
    outcome += '## Acknowledgements {.unnumbered}\n\n';
  }
  return outcome;
}

// note: joinAuthors is based on metadata name arrays
// and joinNames is based on csl json name arrays
function joinAuthors(authors, withemails = false, withaffils = false) {
  let rv = '';
  for (let n = 0; n < authors.length; n++) {
    const authorinfo = authors[n];
    rv += (n > 0)
      ? ((n < (authors.length - 1)) ? ', ' : ' and ')
      : '';
    rv += authorinfo?.name ?? '';
    if (withemails) rv += ' ' + (authorinfo?.email ?? '');
    if (withaffils) rv += ' ' + (authorinfo?.affiliation ?? '');
  }
  return rv;
}

function joinNames(names, includegiven = false) {
  let rv = '';
  for (let n = 0; n < names.length; n++) {
    const name = names[n];
    rv += (n > 0)
      ? ((n < (names.length - 1)) ? ', ' : ' and ')
      : '';
    if (name?.given && includegiven) {
      rv += name.given + ' ';
    }
    if (name?.["non-dropping-particle"]) {
      rv += name["non-dropping-particle"] + ' ';
    }
    if (name?.family) {
      rv += name.family;
    }
  }
  return rv;
}

function splitIntoSentences(line) {
  const exploded = line.split(' ');
  let rv = '';
  for (let n = 0 ; n < exploded.length ; n++) {
    const word = exploded[n];
    // add last word
    if (n == (exploded.length - 1)) {
      rv += word;
      break;
    }
    // no double spaces
    if (word == '') continue;
    const nextword = exploded[n + 1];
    // if next word starts with a capital and this
    // word ends with a lowercase letter and a punctuation
    // mark ending a sentence, it's a sentence break
    if (/^[\*\('"]*[A-Z]/.test(nextword) &&
        /["'\*\)]*[\.\?!]$/.test(word)) {
      rv += word + '\n';
      continue;
    }
    // if next word starts with a capital and this one
    // ends with a footnote, it's a sentence break
    if (/^[\*\('"]*[A-Z]/.test(nextword) &&
       /[\.\?!]\[\^[0-9]+\]$/.test(word)) {
      rv += word + '\n';
      continue;
    }
    // otherwise add back the word and a space
    rv += word + ' ';
  }
  return rv;
}

function squish(s) {
  return s.replace(/[^A-Za-z]/g,'').toLowerCase();
}
