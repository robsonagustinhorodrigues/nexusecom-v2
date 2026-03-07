from bs4 import BeautifulSoup
import re

with open("Exemplos/Mercado Turbo - Vendas Mercado Livre.html", "r") as f:
    html = f.read()

soup = BeautifulSoup(html, 'html.parser')
# Find a sample order card. Looks like they use grid blocks.
cards = soup.find_all('div', class_=re.compile('col-12 xl:col-4 md:col-6 pt-3 pb-3'))
if cards:
    text = cards[0].get_text(separator='\n', strip=True)
    print("--- FIRST CARD TEXT ---")
    print(text)
else:
    print("Could not find order cards by expected classes. Printing first 1000 visible chars:")
    print(soup.body.get_text(separator='\n', strip=True)[:1000])

