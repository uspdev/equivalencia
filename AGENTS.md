## Controle de Git

- O agente não deve executar `git add`, `git commit`, `git push` ou comandos que alterem o estado do Git.
- Pode executar comandos somente de leitura, como `git status`, `git diff` e `git log`.
- Ao finalizar, deve sugerir os comandos de `git add` e `git commit`, sem executá-los.
- A escolha dos arquivos, da mensagem e a execução do commit cabem ao desenvolvedor.