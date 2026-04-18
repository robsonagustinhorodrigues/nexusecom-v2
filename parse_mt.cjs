const fs = require('fs');
const html = fs.readFileSync('Exemplos/Mercado Turbo - Anúncios Mercado Livre.html', 'utf-8');

// A very simple regex-based extractor to find table rows or cards containing specific keywords
const matches = html.match(/<[^>]+>[^<]*(preço|venda|visita|estoque|criado|promo|MLB|MLU|Concorrente|Saúde|Tempo|Tipo|Tarifa)[^<]*<\/[^>]+>/gi);

if (matches) {
    const unique = [...new Set(matches.map(m => m.replace(/<[^>]+>/g, '').trim()).filter(t => t.length > 3 && t.length < 100))];
    console.log(unique.slice(0, 50).join('\n'));
} else {
    console.log("No matches found.");
}
