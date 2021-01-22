/**
 * Twing dump function based on twig.js dump handler.
 */
const { TwingFunction } = require('twing');

const fn = new TwingFunction('dump', (...args) => {
  // Don't pass arguments to `Array.slice`, that is a performance killer

  const argsCopy = [...args];
  const state = this;

  const EOL = '\n';
  const indentChar = '  ';
  let indentTimes = 0;
  let out = '';
  const indent = function (times) {
    let ind = '';
    while (times > 0) {
      times--;
      ind += indentChar;
    }

    return ind;
  };

  const displayVar = function (variable) {
    out += indent(indentTimes);
    if (typeof variable === 'object') {
      dumpVar(variable);
    } else if (typeof variable === 'function') {
      out += 'function()' + EOL;
    } else if (typeof variable === 'string') {
      out += 'string(' + variable.length + ') "' + variable + '"' + EOL;
    } else if (typeof variable === 'number') {
      out += 'number(' + variable + ')' + EOL;
    } else if (typeof variable === 'boolean') {
      out += 'bool(' + variable + ')' + EOL;
    }
  };

  const dumpVar = function (variable) {
    let i;
    if (variable === null) {
      out += 'NULL' + EOL;
    } else if (variable === undefined) {
      out += 'undefined' + EOL;
    } else if (typeof variable === 'object') {
      out += indent(indentTimes) + typeof variable;
      indentTimes++;
      out +=
        '(' +
        (function (obj) {
          let size = 0;
          let key;
          for (key in obj) {
            if (Object.hasOwnProperty.call(obj, key)) {
              size++;
            }
          }

          return size;
        })(variable) +
        ') {' +
        EOL;
      for (i in variable) {
        if (Object.hasOwnProperty.call(variable, i)) {
          out += indent(indentTimes) + '[' + i + ']=> ' + EOL;
          displayVar(variable[i]);
        }
      }

      indentTimes--;
      out += indent(indentTimes) + '}' + EOL;
    } else {
      displayVar(variable);
    }
  };

  // Handle no argument case by dumping the entire render context
  if (argsCopy.length === 0) {
    argsCopy.push(state.context);
  }

  argsCopy.forEach((variable) => {
    dumpVar(variable);
  });

  return out;
});

module.exports = fn;
