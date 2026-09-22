from rapidocr_onnxruntime import RapidOCR
import json

engine = RapidOCR()
result, elapse = engine('scratch_p01.png')

with open('scratch_ocr_p1.txt', 'w', encoding='utf-8') as f:
    if result:
        for box, text, score in result:
            f.write(f"{score:.2f} | {text} | {box}\n")

print("OCR output written to scratch_ocr_p1.txt, items:", len(result) if result else 0)
