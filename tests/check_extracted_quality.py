import json

with open('scratch_todos_militares.json', encoding='utf-8') as f:
    data = json.load(f)

print(f"Total records in JSON: {len(data)}")

# Check missing saram or nome
missing_saram = [d for d in data if not d.get('saram')]
missing_nome = [d for d in data if not d.get('nome')]
print(f"Missing Saram: {len(missing_saram)}")
print(f"Missing Nome: {len(missing_nome)}")

postos = {}
categorias = {}
quadros = {}

for d in data:
    p = d.get('posto', '')
    postos[p] = postos.get(p, 0) + 1
    c = d.get('categoria', '')
    categorias[c] = categorias.get(c, 0) + 1
    q = d.get('quadro', '')
    quadros[q] = quadros.get(q, 0) + 1

print("\n--- DISTRIBUIÇÃO POR POSTO ---")
for p, count in sorted(postos.items(), key=lambda x: x[1], reverse=True):
    print(f"  {p:15s}: {count}")

print("\n--- DISTRIBUIÇÃO POR CATEGORIA ---")
for c, count in sorted(categorias.items(), key=lambda x: x[1], reverse=True):
    print(f"  {c:15s}: {count}")
