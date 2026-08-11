# Git — Conventions et workflow

## Format de commit

```text
<emoji> <type>(<scope>): <sujet>
```

| Emoji | Type     | Quand                                        |
| ----- | -------- | -------------------------------------------- |
| ✨    | feat     | Nouvelle fonctionnalité                      |
| 🔧    | fix      | Correction de bug                            |
| 📖    | docs     | Documentation uniquement                     |
| ♻️    | refactor | Refactorisation sans nouvelle feature ni fix |
| ⚡    | perf     | Amélioration des performances                |
| ✅    | test     | Ajout/correction de tests                    |
| 🏗️    | build    | Système de build, dépendances                |
| 🏭    | ci       | Configuration CI, scripts de déploiement     |
| 🛠️    | chore    | Outillage, config, nettoyage                 |
| 🎨    | style    | Formatage, espaces (pas de logique)          |
| 🔒    | security | Correctifs de sécurité                       |
| ⏪    | revert   | Annulation d'un commit                       |
| 🚧    | WIP      | Travail en cours (éviter sur main)           |

**Règles non négociables :**

- Emoji TOUJOURS présent
- Scope **explicite** : nom de classe/module — pas `feat(api)` générique
- Commits **atomiques** — jamais `git add .` en un bloc
- Jamais de commit direct sur `main`
- Pas de `Co-Authored-By: Claude`

## Workflow type

```bash
git checkout main && git pull
git checkout -b feat/#123-nom-explicite   # toujours lier à un ticket

git add src/fichier-concerne.php          # stager par groupe logique
git commit -m "✨ feat(NomClasse): description courte"
# répéter pour chaque groupe logique
```

## Workflow issue → branche → PR

1. **Créer le ticket** sur GitHub (template bug / feature / chore)
2. **Nommer la branche** : `<type>/#<numéro>-<slug-court>`
3. **Corps de la PR** : inclure `Closes #<numéro>` pour fermer le ticket au merge
4. **Labels PR** : appliquer le même label que le ticket

```bash
git checkout -b feat/#42-nom-explicite
gh pr create --title "✨ feat(NomClasse): description courte" \
  --body "Closes #42" --label "feature"
```

## Limites autonomie — confirmation obligatoire avant de

- Pusher (`git push`)
- Merger dans `main`
- Ouvrir / fermer une PR
- Supprimer une branche ou des fichiers non triviaux

Le user décide de l'ouverture des PRs et des merges.

## Slash command `/git`

Tape `/git` dans le chat pour déclencher le workflow guidé : analyse de `git status` et `git diff`, regroupement par responsabilité, messages au bon format, contrôle d'absence de secrets, et validation avant chaque commit. Définie dans [.claude/commands/git.md](commands/git.md).
