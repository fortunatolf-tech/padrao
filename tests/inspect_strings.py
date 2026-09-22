# Let's map known strings from the first 7 rows to the byte sequences!
import pypdf
import re

pdf_path = r'C:\Users\comara\.gemini\antigravity\brain\a6f617f7-e068-4dd6-bcf1-40cecdd7b153\.user_uploaded\media_1790015382697.pdf'
reader = pypdf.PdfReader(pdf_path)
page0 = reader.pages[0]
raw0 = page0.get_contents().get_data()

tokens = re.findall(rb'(\((?:[^()\\]|\\.)*\)|\[.*?\])\s*(Tj|TJ)', raw0, re.DOTALL)

def extract_strings_from_token(tok):
    # Extracts all parenthesized strings inside a TJ or Tj
    res = []
    # match (string)
    parts = re.findall(rb'\((.*?)(?<!\\)\)', tok)
    for p in parts:
        # unescape
        p = p.replace(b'\\(', b'(').replace(b'\\)', b')').replace(b'\\\\', b'\\')
        res.append(p)
    return b''.join(res)

print("Tokens extracted:")
for i in range(8, 36):
    s = extract_strings_from_token(tokens[i][0])
    print(f"{i}: {s}")
