import sys
import subprocess

def instalar_dependencias():
    print("Verificando e instalando dependências...")
    required = {'os', 'shutil'}
    
    # Verificar se pip está instalado
    try:
        subprocess.check_call([sys.executable, "-m", "pip", "--version"])
    except subprocess.CalledProcessError:
        print("Instalando pip...")
        subprocess.check_call([sys.executable, "-m", "ensurepip"])
    
    # Instalar pacotes necessários
    for package in required:
        try:
            __import__(package)
            print(f"{package} já está instalado")
        except ImportError:
            print(f"Instalando {package}...")
            subprocess.check_call([sys.executable, "-m", "pip", "install", package])

# Instalar dependências antes de continuar
instalar_dependencias()

import os
import shutil

# Lista de arquivos para mover
arquivos = [
    'simulador.html',
    'simulador_com_parcelas.html',
    'listadeicones.html',
    'slidedash.html',
    'banco_dia09deabril.sql',
    'banco_dia11deabril.sql',
    'inserir_emprestimos.sql',
    'emprestimos.sql',
    'alteracoes_emprestimos.sql',
    os.path.join('emprestimos', 'teste_lucro_json.php'),
    os.path.join('emprestimos', 'atualizar_parcelas.sql'),
    'trello_import_git_whatsapp_fluxo.csv',
    'github_projects_tarefas_expandidas.csv',
    'MVP - BASE',
    'menuia-main.zip',
    'estilo.css'
]

print("\nIniciando a movimentação dos arquivos...")

# Nome da pasta de destino
pasta_destino = 'NÃO USADOS'

# Criar a pasta de destino se não existir
if not os.path.exists(pasta_destino):
    os.makedirs(pasta_destino)
    print(f"Pasta '{pasta_destino}' criada com sucesso")

# Mover cada arquivo
arquivos_movidos = 0
arquivos_nao_encontrados = 0
erros = 0

for arquivo in arquivos:
    if os.path.exists(arquivo):
        try:
            shutil.move(arquivo, os.path.join(pasta_destino, os.path.basename(arquivo)))
            print(f'✓ Movido: {arquivo}')
            arquivos_movidos += 1
        except Exception as e:
            print(f'✗ Erro ao mover {arquivo}: {str(e)}')
            erros += 1
    else:
        print(f'! Arquivo não encontrado: {arquivo}')
        arquivos_nao_encontrados += 1

print(f"\nResumo:")
print(f"- Arquivos movidos com sucesso: {arquivos_movidos}")
print(f"- Arquivos não encontrados: {arquivos_nao_encontrados}")
print(f"- Erros ao mover: {erros}") 