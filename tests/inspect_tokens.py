import pypdf
import re

pdf_path = r'C:\Users\comara\.gemini\antigravity\brain\a6f617f7-e068-4dd6-bcf1-40cecdd7b153\.user_uploaded\media_1790015382697.pdf'
reader = pypdf.PdfReader(pdf_path)

print(f"Total pages: {len(reader.pages)}")

# Extract text chunks from page 0
page0 = reader.pages[0]
raw0 = page0.get_contents().get_data()

# Find all font operations and text operations
# In PDF: /FontName size Tf ... text Tj or TJ
tokens = re.findall(rb'(\((?:[^()\\]|\\.)*\)|\[.*?\])\s*(Tj|TJ)', raw0, re.DOTALL)
print(f"Page 0 text tokens count: {len(tokens)}")

for i, (tok, op) in enumerate(tokens[:35]):
    print(f"{i}: {op} -> {tok}")
