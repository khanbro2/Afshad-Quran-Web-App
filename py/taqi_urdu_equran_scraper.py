import requests
from bs4 import BeautifulSoup
import json
import csv
import time
import re

BASE_URL = "https://equranlibrary.com/translation/taqi/{}"
OUTPUT_JSON = "mufti_taqi_usmani_urdu.json"
OUTPUT_CSV = "mufti_taqi_usmani_urdu.csv"

HEADERS = {
    "User-Agent": "Mozilla/5.0"
}

all_data = []

def extract_surah(surah_no):
    url = BASE_URL.format(surah_no)
    r = requests.get(url, headers=HEADERS, timeout=30)
    r.raise_for_status()

    soup = BeautifulSoup(r.text, "lxml")
    text = soup.get_text("\n", strip=True)
    lines = [line.strip() for line in text.splitlines() if line.strip()]

    try:
        ayah_idx = lines.index("Ayah")
    except ValueError:
        print(f"[WARN] 'Ayah' marker not found in Surah {surah_no}")
        return []

    trans_idx = None
    for i in range(ayah_idx + 1, len(lines)):
        if lines[i] == "Translation":
            trans_idx = i
            break

    if trans_idx is None:
        print(f"[WARN] 'Translation' marker not found after Ayah in Surah {surah_no}")
        return []

    ayah_numbers = []
    for line in lines[ayah_idx + 1:trans_idx]:
        nums = re.findall(r"\d+", line)
        ayah_numbers.extend(int(n) for n in nums)

    print("DEBUG", surah_no, "ayah_idx=", ayah_idx, "trans_idx=", trans_idx, "ayah_numbers_sample=", ayah_numbers[:10])

    if not ayah_numbers:
        print(f"[WARN] No ayah numbers found in Surah {surah_no}")
        return []

    max_ayah = max(ayah_numbers)

    start_idx = None
    for i in range(trans_idx + 1, len(lines)):
        if "Get Android App" in lines[i]:
            start_idx = i + 1
            break

    if start_idx is None:
        start_idx = trans_idx + 1

    records = []
    i = start_idx
    while i < len(lines):
        line = lines[i]

        if line == "Top":
            break

        if re.fullmatch(r"\d+", line):
            ayah = int(line)

            if 1 <= ayah <= max_ayah:
                translation = None
                j = i + 1
                while j < len(lines):
                    nxt = lines[j].strip()

                    if nxt == "Top":
                        break
                    if re.fullmatch(r"\d+", nxt):
                        break

                    # Arabic Quran lines usually contain tashkeel/end marks
                    if re.search(r"[ۭۙۚۗۖۘ۩ؕؗ]", nxt):
                        j += 1
                        continue

                    translation = nxt
                    break

                if translation:
                    records.append({
                        "surah": surah_no,
                        "ayah": ayah,
                        "translation_urdu": translation
                    })

        i += 1

    dedup = {}
    for row in records:
        dedup[row["ayah"]] = row

    return [dedup[a] for a in sorted(dedup)]

for surah in range(1, 115):
    print(f"Scraping Surah: {surah}")
    try:
        rows = extract_surah(surah)
        print(f"  -> extracted {len(rows)} ayat")
        all_data.extend(rows)
    except Exception as e:
        print(f"[ERROR] Surah {surah}: {e}")
    time.sleep(0.4)

with open(OUTPUT_JSON, "w", encoding="utf-8") as f:
    json.dump(all_data, f, ensure_ascii=False, indent=2)

with open(OUTPUT_CSV, "w", encoding="utf-8-sig", newline="") as f:
    writer = csv.writer(f)
    writer.writerow(["surah", "ayah", "translation_urdu"])
    for row in all_data:
        writer.writerow([row["surah"], row["ayah"], row["translation_urdu"]])

print(f"Done! Total records: {len(all_data)}")
print(f"Saved: {OUTPUT_JSON}")
print(f"Saved: {OUTPUT_CSV}")