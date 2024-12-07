// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

// File: formreader.mjs
// a function that returns object containing key→value pairings for
// input, select, and textarea fields inside the specified element

export default function getformfields(elem) {
  const rv = {};
  // get all the elements
  const inpinp = elem.getElementsByTagName("input");
  const selsel = elem.getElementsByTagName("select");
  const txatxa = elem.getElementsByTagName("textarea");
  // keep a tally of unknowns so we can add them with the
  // keys "unknown1", "unknown2", etc.
  // keep track of possibly unchecked radio buttons
  const radios = {};
  let unknowns = 0;
  let anyinvalid = false;
  for (const inp of [...inpinp, ...txatxa, ...selsel]) {
    // try to check their validity
    try {
      if (!inp.checkValidity()) {
        inp.setAttribute("aria-invalid", "true");
        anyinvalid = true;
        inp.addEventListener('change', function() {
          if (this.checkValidity()) {
            this.removeAttribute("aria-invalid");
          }
        });
      }
    } catch(err) {};
    // name is the name field, or if it doesn't have one, the id,
    // or it has neither, then "unknown"
    let name = ((inp.name) ? inp.name :
                ((inp.id) ? inp.id : 'unknown'));
    if (name == 'unknown') {
      unknowns++;
      name = name + (unknowns.toString());
    }
    // suppress duplicates
    if (name in rv) { continue; }
    let val = ((inp?.value) ? inp.value : '');
    // checkboxes are booleans
    if (inp.tagName.toLowerCase() == 'input' && inp.type == 'checkbox') {
      val = (inp.checked);
    }
    if (inp.tagName.toLowerCase() == 'input' && inp.type == 'radio') {
      //keep track of how many radios with this name have been found
      if (!(name in radios)) { radios[name] = 0; }
      // check if part of a group
      if (inp.name) {
        if (inp.checked) {
          // if it's checked the value is either its value
          // or its index, which should be the number of
          // unchecked radios with this name found before.
          if (inp?.value && inp?.value != 'on') {
            val = inp.value;
          } else {
            val = radios[name];
          }
        } else {
          // if unchecked, wait for what is checked
          radios[name] = radios[name] + 1;
          continue;
        }
      } else {
        // if not part of a group, record whether it is checked or not
        val = (inp.checked);
      }
    }
    rv[name] = val;
  }
  // if no radio was checked for a group, it has value -1
  for (const radioname in radios) {
    if (!(radioname in rv)) { rv[radioname] = -1; };
  }
  // if any were invalid, report it
  rv.anyinvalid = anyinvalid;
  return rv;
}
