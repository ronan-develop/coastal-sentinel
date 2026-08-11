# Workflow tickets & planification — NON NÉGOCIABLE

Au même niveau que les conventions git : ces règles ne se contournent pas.

---

## Deux règles absolues

1. **Aucune ligne de code sans ticket GitHub associé.** Le ticket se crée
   **avant** d'écrire quoi que ce soit, via `gh`.
2. **Aucun code sans plan de tâche validé.** Le plan est présenté, l'utilisateur
   valide **explicitement**, et seulement ensuite le code commence.

Si une demande arrive sans ticket ni plan → créer le ticket et proposer le plan
d'abord. Ne jamais coder « en avance ».

---

## Flux imposé, dans l'ordre

```text
ticket  →  plan validé  →  branche  →  TDD (RED→GREEN→REFACTOR)  →  commits  →  PR (Closes #n)
```

Chaque étape dépend de la précédente. On ne saute aucune étape.

---

## 1. Créer le ticket (`gh`)

```bash
gh issue create \
  --title "<titre explicite>" \
  --label "<feature|bug|chore>" \
  --body "<contexte + critères d'acceptation>"
```

Kanban de suivi : [github.com/users/ronan-develop/projects/9](https://github.com/users/ronan-develop/projects/9/views/1).
Rattachement **automatique** à la création via
[.github/workflows/add-to-project.yml](../.github/workflows/add-to-project.yml)
— aucune commande manuelle nécessaire.

### Colonnes du Kanban ↔ étapes du flux

| Colonne       | Correspond à                                                   |
| ------------- | -------------------------------------------------------------- |
| `Backlog`     | Ticket créé, pas encore de plan validé                         |
| `Ready`       | Plan validé, prêt à démarrer (branche pas encore créée)        |
| `In progress` | Branche créée, développement en TDD en cours                   |
| `In review`   | PR ouverte, en attente de merge                                |
| `Done`        | PR mergée (le ticket se ferme automatiquement via `Closes #n`) |

Déplacer manuellement la carte à chaque changement d'étape (pas d'automatisation
au-delà de la création → `Backlog` et de la fermeture → `Done`, gérées par
GitHub par défaut).

| Label     | Usage                                             |
| --------- | ------------------------------------------------- |
| `feature` | Nouvelle fonctionnalité                           |
| `bug`     | Correction d'un comportement défectueux           |
| `chore`   | Outillage, config, dette technique, documentation |

Le corps du ticket décrit **le quoi et le pourquoi** + les **critères
d'acceptation** (ce qui permettra de dire « c'est fini »).

## 2. Planifier la tâche

Avant tout code, présenter un plan : fichiers touchés, approche, tests prévus,
points d'incertitude. **Attendre la validation explicite de l'utilisateur.**

## 3. Créer la branche liée au ticket

```bash
git checkout main && git pull
git checkout -b <type>/#<numéro>-<slug-court>
```

Nommage : `feat/#42-slug`, `fix/#67-slug`, `chore/#81-slug` — voir
[git-conventions.md](git-conventions.md).

## 4. Développer en TDD

RED → GREEN → REFACTOR. Le test échoue d'abord, jamais l'inverse.

## 5. Commits atomiques

Format et découpage : [git-conventions.md](git-conventions.md).

## 6. Ouvrir la PR

```bash
gh pr create --base main --title "<emoji> <type>(<scope>): <sujet>" \
  --body "Closes #<numéro>" --label "<même label que le ticket>"

# systématique, toujours juste après : milestone identique au ticket fermé
gh pr edit <numéro-pr> --milestone "<même milestone que le ticket>"
```

`Closes #<numéro>` ferme automatiquement le ticket au merge.

**Non négociable** : la milestone de la PR doit toujours refléter celle du
ticket qu'elle ferme. L'assignation au Kanban et l'assignee (`ronan-develop`)
sont automatiques via `.github/workflows/add-to-project.yml` (déclenché à
l'ouverture, issue ou PR) — mais la milestone d'une PR ne peut pas être
déduite automatiquement du ticket lié sans logique supplémentaire ; c'est
donc une étape manuelle **systématique**, jamais oubliée, exécutée juste
après chaque `gh pr create`.

## 7. Après le merge — journal non technique

Une fois la PR mergée, évaluer si l'étape franchie apporte une plus-value
suffisante pour un lecteur non développeur (CRC, partenaires) — typiquement :
nouvelle brique fonctionnelle, décision structurante, jalon terrain. Si oui,
compléter `avancements.md` (racine du repo, non commité, non tracké) avec
une nouvelle entrée de journal datée. Ne pas y consigner du pur outillage
interne (config CI, lint, etc.) sans intérêt pour ce public.

---

## Limites autonomie — confirmation obligatoire avant de

- Créer un ticket, ouvrir/fermer une PR
- Pusher, merger dans `main`, supprimer une branche

L'utilisateur décide. Ces actions ne sont jamais lancées sans son accord.
