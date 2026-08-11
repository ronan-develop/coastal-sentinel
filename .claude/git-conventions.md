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
- **Jamais de squash merge** — toujours un merge commit classique (`gh pr merge --merge`), pour garder la trace complète de chaque commit atomique

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

# au merge, toujours en merge commit — jamais --squash ni --rebase
gh pr merge --merge

# nettoyage post-merge : supprimer la branche, local + remote
git branch -d feat/#42-nom-explicite
git push origin --delete feat/#42-nom-explicite
```

## Nettoyage post-merge

Une fois une PR mergée, **supprimer la branche** — en local **et** sur le
remote. Ne pas laisser traîner de branches déjà mergées.

## Limites autonomie — confirmation obligatoire avant de

- Pusher (`git push`)
- Merger dans `main`
- Ouvrir / fermer une PR
- Supprimer une branche ou des fichiers non triviaux (y compris le nettoyage post-merge ci-dessus)

Le user décide de l'ouverture des PRs et des merges.

## Vérifications automatiques

| Contrôle                                     | Où                                              | Portée                           |
| -------------------------------------------- | ----------------------------------------------- | -------------------------------- |
| Format du message de commit                  | `.githooks/commit-msg` (local)                  | Chaque commit local              |
| Interdiction de commit direct sur `main`     | `.githooks/pre-commit` (local)                  | Chaque commit local              |
| Interdiction de push direct sur `main`       | `.githooks/pre-push` (local)                    | Chaque push local                |
| Format du titre de PR                        | `.github/workflows/lint-pr-title.yml` (GitHub)  | Ouverture/édition de PR          |
| Interdiction de push/merge direct sur `main` | Protection de branche GitHub (`enforce_admins`) | Toute tentative, y compris admin |

**Activation des hooks locaux** (une fois par clone) :

```bash
git config core.hooksPath .githooks
```

Les hooks locaux ne voient que les commandes `git` exécutées en local — ils ne
peuvent pas intercepter `gh pr create`/`gh pr merge`, qui parlent directement à
l'API GitHub. D'où le lint de titre de PR côté GitHub Actions, complémentaire.

## Slash command `/git`

Tape `/git` dans le chat pour déclencher le workflow guidé : analyse de `git status` et `git diff`, regroupement par responsabilité, messages au bon format, contrôle d'absence de secrets, et validation avant chaque commit. Définie dans [.claude/commands/git.md](commands/git.md).
