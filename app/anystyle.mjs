// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: anystyle.mjs
// defines functions for working with the output of anystyle csl json

const letters = 'abdefghijklmnopqrstuvwxyz';

function asciiize(s) {
  return s.replace(/[äáàâ]/g,'a')
    .replace(/[ç]/g,'c')
    .replace(/[éëêè]/g,'e')
    .replace(/[ïíìî]/g,'i')
    .replace(/[öóòøô]/g,'o')
    .replace(/[üúùû]/g,'u')
    .replace(/[ß]/g,'ss')
    .replace(/[ñ]/g,'n');
}

function fixGiven(given) {
  given = given.trim();
  if (/^[A-Z]\. *[A-Z]\. *[A-Z]\.$/.test(given)) {
    given = given.replace(
      /^([A-Z])\. *([A-Z])\. *([A-Z])\.$/,
      "$1. $2. $3."
    );
  }
  if (/^[A-Z]\. *[A-Z]\.$/.test(given)) {
    given = given.replace(
      /^([A-Z])\. *([A-Z])\.$/,
      "$1. $2."
    );
  }
  return given;
}

function nameMerge(people) {
  if (!people) return '';
  return people.map((x) => (x?.family ?? '')).join('');
}

export function fixAnyStyle(anystyleitems) {
  const ids = [];
  for (const asitem of anystyleitems) {

    // trim title, container title
    if (asitem?.title) asitem.title = asitem.title.trim();
    if (asitem?.["container-title"]) {
      asitem["container-title"] = asitem["container-title"].trim();
    }

    // fix issued date
    if (asitem?.issued) {
      let year = parseInt(asitem.issued);
      if (isNaN(year)) year = asitem.issued;
      asitem.issued = {
        "date-parts": [[year]]
      }
    }

    // fix mames, initials
    for (const role of ["author", "editor"]) {
      if (asitem?.[role] && asitem[role]?.length > 0) {
        for (const person of asitem[role]) {
          if (person?.family) {
            person.family = person.family.trim();
          }
          if (person?.given) {
            person.given = fixGiven(person.given);
          }
        }
      }
    }

    // add id
    if (asitem?.id) {
      ids.push(asitem?.id);
    } else {
      let mergednames = nameMerge(asitem?.author);
      if (mergednames == '') {
        mergednames = nameMerge(asitem?.editor);
      }
      if (mergednames == '') {
        mergednames = 'Anonymous'
      }
      const year = asitem?.issued?.["date-parts"]?.[0]?.[0]?.toString()
        ?? 'forthcoming';
      let id = asciiize((mergednames + year).toLowerCase());
      let letterindex = -1;
      while (ids.includes(id) && letterindex < 25) {
        letterindex++;
        const letter = letters[letterindex];
        id = asciiize((mergednames + year + letter).toLowerCase());
      }
      asitem.id = id;
      ids.push(id);
    }
  }
  return anystyleitems;
}