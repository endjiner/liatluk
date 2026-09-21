const fs = require('fs');
const path = require('path');

const targetFile = path.resolve(__dirname, '../public/assets/css/tailwind.css');

if (!fs.existsSync(targetFile)) {
  console.error('File not found:', targetFile);
  process.exit(1);
}

let c = fs.readFileSync(targetFile, 'utf8');

// 1. Hapus komentar
c = c.replace(/\/\*[\s\S]*?\*\//g, '');
c = c.replace(/\r\n/g, '\n');

// 2. Parser CSS
function formatCssRules(cssText, indent = '') {
  let blocks = [];
  let buffer = '';
  let depth = 0;
  let inString = false;
  let stringChar = '';

  for (let i = 0; i < cssText.length; i++) {
    const char = cssText[i];
    if (inString) {
      buffer += char;
      if (char === stringChar && cssText[i - 1] !== '\\') {
        inString = false;
      }
      continue;
    }

    if (char === '"' || char === "'") {
      inString = true;
      stringChar = char;
      buffer += char;
      continue;
    }

    if (char === '{') {
      depth++;
      buffer += char;
    } else if (char === '}') {
      depth--;
      buffer += char;
      if (depth === 0) {
        const rule = buffer.trim();
        buffer = '';
        if (rule) {
          blocks.push(formatSingleBlock(rule, indent));
        }
      }
    } else {
      buffer += char;
    }
  }

  // Gabungkan block: jika block berikutnya atau sebelumnya adalah multi-line, beri baris kosong.
  // Jika keduanya 1 baris, tempel langsung di baris berikutnya tanpa jeda baris kosong!
  let output = '';
  for (let i = 0; i < blocks.length; i++) {
    const curr = blocks[i];
    if (i === 0) {
      output += curr;
    } else {
      const prev = blocks[i - 1];
      const isPrevMulti = prev.includes('\n');
      const isCurrMulti = curr.includes('\n');

      if (isPrevMulti || isCurrMulti) {
        output += '\n\n' + curr;
      } else {
        output += '\n' + curr;
      }
    }
  }

  return output;
}

function formatSingleBlock(block, indent = '') {
  const firstBrace = block.indexOf('{');
  if (firstBrace === -1) return indent + block;

  const selector = block.slice(0, firstBrace).trim();
  const body = block.slice(firstBrace + 1, block.length - 1).trim();

  // Jika @media atau @supports
  if (selector.startsWith('@media') || selector.startsWith('@supports')) {
    const inner = formatCssRules(body, indent + '  ');
    return indent + selector + ' {\n' + inner + '\n' + indent + '}';
  }

  // Jika @keyframes
  if (selector.startsWith('@keyframes')) {
    const keyframesInner = body
      .split('}')
      .map(k => k.trim())
      .filter(Boolean)
      .map(k => {
        const idx = k.indexOf('{');
        if (idx === -1) return '';
        const step = k.slice(0, idx).trim();
        const props = k.slice(idx + 1).trim().replace(/;$/, '');
        return indent + '  ' + step + ' { ' + props + '; }';
      })
      .filter(Boolean)
      .join('\n');
    return indent + selector + ' {\n' + keyframesInner + '\n' + indent + '}';
  }

  // Rules CSS biasa
  const decls = body
    .split(';')
    .map(d => d.trim())
    .filter(Boolean);

  if (decls.length === 0) return '';

  // Rule sederhana (1-2 deklarasi pendek) -> 1 baris padat
  if (decls.length <= 2 && decls.join('; ').length < 90) {
    return indent + selector + ' { ' + decls.join('; ') + '; }';
  }

  // Rule yang kompleks -> multi-line rapi
  const formattedDecls = decls.map(d => indent + '  ' + d + ';').join('\n');
  return indent + selector + ' {\n' + formattedDecls + '\n' + indent + '}';
}

const formatted = formatCssRules(c);
fs.writeFileSync(targetFile, formatted.trim() + '\n', 'utf8');
console.log('Successfully formatted CSS compactly without wasted blank lines.');
