from PIL import Image
from rapidocr_onnxruntime import RapidOCR
import numpy as np

im = Image.open('scratch_pages/page_01.png')
# Table region: X from 450 to 2335, Y from 530 to 1580
crop = im.crop((450, 530, 2335, 1580))
crop.save('scratch_crop01.png')

engine = RapidOCR()
res, _ = engine(np.array(crop))

print("Results in table area:")
for box, text, score in res:
    # box relative to crop
    x = box[0][0] + 450
    y = box[0][1] + 530
    print(f"Y={y:.0f}, X={x:.0f} | {text}")
