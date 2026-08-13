const fs = require('fs');
const path = require('path');
const vm = require('vm');

const rootDir = 'c:\\Users\\robso\\Dropbox\\Vinutto\\CRM';

const filesToCheck = [
  'cadastro-crm.js',
  'imoveis-loader.js',
  'imoveis-data.js',
  'index.html',
  'teste.html',
  'api.php',
  'assets/api.php',
  'assets/js/script.js',
  'assets/js/app.js',
  'assets/js/admin.js',
  'assets/js/auth.js',
  'assets/js/leads.js',
  'assets/js/login.js',
];

const results = {
  syntaxErrors: [],
  duplicateFunctions: [],
  duplicateObjectKeys: [],
  redundantImports: [],
  fileComparisons: []
};

// Check syntax of a JS string
function checkJsSyntax(code, filename, lineOffset = 0) {
  try {
    new vm.Script(code, { filename, lineOffset });
  } catch (err) {
    results.syntaxErrors.push({
      file: filename,
      message: err.message,
      stack: err.stack,
      line: err.stack.match(/:(\d+):/)?.[1] ? parseInt(err.stack.match(/:(\d+):/)[1]) + lineOffset : 'unknown'
    });
  }
}

// Find function definitions in JS
function findJsFunctions(code, filename) {
  const funcs = [];
  
  // Pattern 1: function name(...)
  const p1 = /(?:async\s+)?function\s+([a-zA-Z0-9_$]+)\s*\(/g;
  let match;
  while ((match = p1.exec(code)) !== null) {
    funcs.push({ name: match[1], line: getLineNumber(code, match.index) });
  }

  // Pattern 2: const/let/var name = function ... or name = (...) =>
  // e.g. window.name = function or window.name = async function or const name =
  const p2 = /(?:const|let|var|window\s*\.)\s*([a-zA-Z0-9_$]+)\s*=\s*(?:async\s+)?(?:function|\([^)]*\)\s*=>)/g;
  while ((match = p2.exec(code)) !== null) {
    funcs.push({ name: match[1], line: getLineNumber(code, match.index) });
  }

  // Check for duplicates
  const seen = {};
  funcs.forEach(f => {
    if (seen[f.name]) {
      results.duplicateFunctions.push({
        file: filename,
        name: f.name,
        lines: [seen[f.name], f.line]
      });
    } else {
      seen[f.name] = f.line;
    }
  });
}

// Find function definitions in PHP
function findPhpFunctions(code, filename) {
  const funcs = [];
  const p = /function\s+([a-zA-Z0-9_$]+)\s*\(/g;
  let match;
  while ((match = p.exec(code)) !== null) {
    funcs.push({ name: match[1], line: getLineNumber(code, match.index) });
  }

  const seen = {};
  funcs.forEach(f => {
    if (seen[f.name]) {
      results.duplicateFunctions.push({
        file: filename,
        name: f.name,
        lines: [seen[f.name], f.line]
      });
    } else {
      seen[f.name] = f.line;
    }
  });
}

function getLineNumber(code, index) {
  return code.substring(0, index).split('\n').length;
}

// Check for duplicate keys in JS object literals (rough check using regex and parsing chunks)
function checkDuplicateKeys(code, filename) {
  // Try to find object literals, but to be simple and accurate we can check if there are obvious duplicate keys
  // Let's look at lines with "key:" in object declarations or switch cases
  // A simpler way: we can check for duplicate case statements in switch blocks
  const switchPattern = /switch\s*\([^)]*\)\s*\{([^}]*)\}/g;
  let match;
  while ((match = switchPattern.exec(code)) !== null) {
    const block = match[1];
    const casePattern = /case\s+['"]?([a-zA-Z0-9_$]+)['"]?\s*:/g;
    let cMatch;
    const cases = [];
    while ((cMatch = casePattern.exec(block)) !== null) {
      cases.push({ name: cMatch[1], line: getLineNumber(code, match.index + cMatch.index) });
    }
    const seen = {};
    cases.forEach(c => {
      if (seen[c.name]) {
        results.duplicateObjectKeys.push({
          file: filename,
          type: 'switch-case',
          key: c.name,
          lines: [seen[c.name], c.line]
        });
      } else {
        seen[c.name] = c.line;
      }
    });
  }
}

// Main logic
filesToCheck.forEach(file => {
  const filePath = path.join(rootDir, file);
  if (!fs.existsSync(filePath)) {
    console.log(`File not found: ${file}`);
    return;
  }
  const content = fs.readFileSync(filePath, 'utf-8');

  if (file.endsWith('.js')) {
    // JS Syntax (compiled via vm.Script)
    checkJsSyntax(content, file);
    findJsFunctions(content, file);
    checkDuplicateKeys(content, file);
  } else if (file.endsWith('.html')) {
    // Extract inline JS
    const scriptPattern = /<script\b[^>]*>([\s\S]*?)<\/script>/gi;
    let match;
    let scriptIdx = 0;
    const imports = [];
    while ((match = scriptPattern.exec(content)) !== null) {
      const scriptAttr = match[0];
      const scriptCode = match[1];
      const startLine = getLineNumber(content, match.index);
      
      // Check if it's an external script or inline
      if (scriptAttr.includes('src=')) {
        const srcMatch = scriptAttr.match(/src=["']([^"']+)["']/i);
        if (srcMatch) {
          imports.push({ src: srcMatch[1], line: startLine });
        }
      } else {
        scriptIdx++;
        checkJsSyntax(scriptCode, `${file} (inline #${scriptIdx})`, startLine - 1);
        findJsFunctions(scriptCode, `${file} (inline #${scriptIdx}, starts at line ${startLine})`);
        checkDuplicateKeys(scriptCode, `${file} (inline #${scriptIdx})`);
      }
    }

    // Check duplicate imports in HTML
    const seenImports = {};
    imports.forEach(imp => {
      if (seenImports[imp.src]) {
        results.redundantImports.push({
          file: file,
          src: imp.src,
          lines: [seenImports[imp.src], imp.line]
        });
      } else {
        seenImports[imp.src] = imp.line;
      }
    });
  } else if (file.endsWith('.php')) {
    findPhpFunctions(content, file);
  }
});

// Compare api.php in root vs assets/api.php
const rootApi = path.join(rootDir, 'api.php');
const assetsApi = path.join(rootDir, 'assets/api.php');
if (fs.existsSync(rootApi) && fs.existsSync(assetsApi)) {
  const rContent = fs.readFileSync(rootApi, 'utf-8');
  const aContent = fs.readFileSync(assetsApi, 'utf-8');
  results.fileComparisons.push({
    files: ['api.php', 'assets/api.php'],
    status: rContent === aContent ? 'identical' : 'different',
    rootSize: rContent.length,
    assetsSize: aContent.length
  });
}

console.log(JSON.stringify(results, null, 2));
