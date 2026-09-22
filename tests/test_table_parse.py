import glob
from PIL import Image
from rapidocr_onnxruntime import RapidOCR
import numpy as np
import re

engine = RapidOCR()

def parse_page(img_path):
    im = Image.open(img_path)
    crop = im.crop((450, 520, 2335, 1530))
    res, _ = engine(np.array(crop))
    if not res:
        return []

    tokens = []
    for box, text, score in res:
        x = box[0][0] + 450
        y = box[0][1] + 520
        tokens.append({'x': x, 'y': y, 'text': text.strip(), 'score': score})

    # Sort tokens by Y
    tokens.sort(key=lambda t: t['y'])

    # Find Saram tokens (usually 6-8 digits, around X in [1050, 1190])
    # Or find Row Number at X < 500
    rows = []
    # Identify row anchors using Saram or ID or Row number
    row_anchors = []
    for t in tokens:
        # Check if t is saram: 6 to 8 digits
        if 1050 <= t['x'] <= 1190 and re.match(r'^\d{5,8}$', t['text']):
            row_anchors.append(t['y'])

    if not row_anchors:
        # Fallback to row number at X < 500
        for t in tokens:
            if t['x'] < 500 and re.match(r'^\d+$', t['text']):
                row_anchors.append(t['y'])

    # Deduplicate anchors that are within 30px
    dedup_anchors = []
    for a in row_anchors:
        if not dedup_anchors or (a - dedup_anchors[-1]) > 35:
            dedup_anchors.append(a)

    parsed_rows = []
    for i, ay in enumerate(dedup_anchors):
        # Y window for this row
        y_min = ay - 35
        y_max = dedup_anchors[i+1] - 15 if i + 1 < len(dedup_anchors) else ay + 80

        row_tokens = [t for t in tokens if y_min <= t['y'] < y_max]

        # Group by column X
        nome_parts = []
        nome_guerra = ""
        saram = ""
        posto = ""
        quadro = ""
        esp = ""
        setor = ""
        telefone = ""
        row_id = ""

        for t in row_tokens:
            x = t['x']
            txt = t['text']
            if x < 500:
                continue
            elif 600 <= x < 865:
                nome_parts.append(txt)
            elif 865 <= x < 1050:
                nome_guerra = (nome_guerra + " " + txt).strip()
            elif 1050 <= x < 1190:
                if re.match(r'^\d+$', txt):
                    saram = txt
                else:
                    nome_guerra = (nome_guerra + " " + txt).strip()
            elif 1190 <= x < 1300:
                posto = (posto + " " + txt).strip()
            elif 1300 <= x < 1440:
                quadro = (quadro + " " + txt).strip()
            elif 1440 <= x < 1625:
                esp = (esp + " " + txt).strip()
            elif 1625 <= x < 1910:
                setor = (setor + " " + txt).strip()
            elif 1910 <= x < 2050:
                if re.match(r'^\d+$', txt):
                    telefone = txt
            elif x >= 2250:
                if re.match(r'^\d+$', txt):
                    row_id = txt

        nome = " ".join(nome_parts)
        parsed_rows.append({
            'nome': nome,
            'nome_guerra': nome_guerra,
            'saram': saram,
            'posto': posto,
            'quadro': quadro,
            'especialidade': esp,
            'setor': setor,
            'telefone': telefone,
            'joomla_id': row_id
        })

    return parsed_rows

p1 = parse_page('scratch_pages/page_01.png')
print("--- Page 1 Parsed ---")
for r in p1:
    print(r)

p2 = parse_page('scratch_pages/page_02.png')
print("\n--- Page 2 Parsed ---")
for r in p2:
    print(r)
