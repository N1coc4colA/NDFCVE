# NDFCVE

![License](https://img.shields.io/badge/license-%20%20GNU%20GPLv3%20-green?style=plastic)
![PHP](https://img.shields.io/badge/PHP-8.1-777BB4?logo=php)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker)

**NDFCVE** est une application web de recherche et d'analyse de vulnérabilités CVE (Common Vulnerabilities and Exposures). Elle permet d'interroger plusieurs sources de données de sécurité et de visualiser les informations sur les vulnérabilités de manière intuitive.

## 📋 Table des matières

- [Fonctionnalités](#-fonctionnalités)
- [Prérequis](#-prérequis)
- [Installation](#-installation)
- [Utilisation](#-utilisation)
- [Architecture](#-architecture)
- [Sources de données / API](#-sources-de-données--api)
- [Structure du projet](#-structure-du-projet)
- [Technologies utilisées](#-technologies-utilisées)
- [Contribuer](#-contribuer)
- [License](#-license)

---

## ✨ Fonctionnalités

### 1. Recherche par ID CVE
- Recherche d'une ou plusieurs CVE par identifiant (format `CVE-YYYY-NNNN`)
- Import de fichier texte contenant une liste de CVE (un ID par ligne)
- Validation en temps réel des identifiants CVE
- Vérification automatique de l'existence des CVE dans la base CIRCL
- Indicateur visuel pour les CVE inexistantes

### 2. Recherche par mot-clé
- Recherche de CVE par vendor/product (ex: Oracle, Microsoft, Apache)
- Filtrage par période (1 mois, 3 mois, 6 mois, 1 an)
- Analyse statistique des vulnérabilités trouvées :
  - Score CVSS moyen
  - Score EPSS moyen
  - Nombre de CVE dans le catalogue KEV (Known Exploited Vulnerabilities)
  - Sévérité moyenne
- Visualisation graphique :
  - Distribution des scores CVSS
  - Répartition par niveau de sévérité
- Liste des vulnérabilités les plus critiques

### 3. Affichage détaillé des résultats
- Informations complètes sur chaque CVE :
  - Description
  - Score CVSS v3.1
  - Score EPSS (Exploit Prediction Scoring System)
  - Statut KEV (Known Exploited Vulnerabilities)
  - Dates de publication et modification
  - Liens vers les références
- Interface responsive et moderne avec Bootstrap 5
- Loader animé pendant les requêtes

---

## 🎛️ Prérequis

### Option 1 : Déploiement avec Docker (recommandé)
- **Docker** version 20.10 ou supérieure
- **Docker Compose** version 2.0 ou supérieure

### Option 2 : Déploiement manuel
- **PHP** version 8.1 ou supérieure
- **Apache** ou **Nginx** comme serveur web
- Extensions PHP requises :
  - `curl` (pour les appels API)
  - `zip` (inclus dans l'image Docker)
  - `pdo_mysql` (pour une éventuelle base de données)
- **Connexion Internet** (pour accéder aux API externes)

---

## ⚙️ Installation

### Avec Docker (méthode recommandée)

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/votre-username/NDFCVE.git
   cd NDFCVE
   ```

2. **Construire et démarrer les conteneurs**
   ```bash
   docker-compose up -d --build
   ```

3. **Accéder à l'application**
   
   Ouvrez votre navigateur et accédez à :
   ```
   http://localhost
   ```

4. **Arrêter l'application**
   ```bash
   docker-compose down
   ```

### Installation manuelle

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/votre-username/NDFCVE.git
   cd NDFCVE
   ```

2. **Configurer le serveur web**
   
   Configurez Apache/Nginx pour pointer vers le répertoire `src/` comme racine du document.

3. **Vérifier les extensions PHP**
   ```bash
   php -m | grep -E 'curl|zip|pdo_mysql'
   ```

4. **Démarrer le serveur**
   ```bash
   # Avec PHP built-in server (développement uniquement)
   cd src
   php -S localhost:8000
   ```

5. **Accéder à l'application**
   ```
   http://localhost:8000
   ```

---

## 🚀 Utilisation

### Recherche par ID CVE

1. Accédez à la page d'accueil (onglet "ID CVE")
2. Saisissez un ou plusieurs identifiants CVE dans le formulaire
   - Format : `CVE-YYYY-NNNN` (ex: `CVE-2024-1234`)
   - Les CVE inexistantes seront marquées en rouge après validation
3. **OU** importez un fichier `.txt` contenant une liste de CVE (un par ligne)
4. Cliquez sur "Rechercher"
5. Consultez les résultats détaillés pour chaque CVE

### Recherche par mot-clé

1. Accédez à l'onglet "Recherche"
2. Saisissez un mot-clé (vendor ou product) dans le champ de recherche
   - Exemples : `Oracle`, `Microsoft Windows`, `Apache Tomcat`
3. Sélectionnez la période d'analyse (1 mois à 1 an)
4. Cliquez sur "Analyser"
5. Consultez :
   - Les statistiques globales (scores moyens, nombre de CVE dans KEV)
   - Les graphiques de distribution
   - La liste des vulnérabilités les plus critiques

---

## 🧱 Architecture

### Architecture applicative

```
┌─────────────────────────────────────────────────────────┐
│                     Navigateur Client                   │
│  (HTML/CSS/JavaScript - Bootstrap 5 + Chart.js)         │
└────────────────┬────────────────────────────────────────┘
                 │ HTTP/HTTPS
                 ▼
┌─────────────────────────────────────────────────────────┐
│               Serveur Web (Apache + PHP 8.1)            │
│                                                         │
│  ┌──────────────────────────────────────────────────┐   │
│  │  Pages PHP (index.php, keyword.php, results.php) │   │
│  └──────────────────────────────────────────────────┘   │
│                                                         │
│  ┌───────────────────────────────────────────-─────┐    │
│  │         API Proxies (PHP/cURL)                  │    │
│  │  • circl_proxy.php                              │    │
│  │  • kev_proxy.php                                │    │
│  │  • kev_details_proxy.php                        │    │
│  └──────────┬──────────────────────────────────────┘    │
└─────────────┼───────────────────────────────────────────┘
              │ API Calls (cURL)
              ▼
┌─────────────────────────────────────────────────────────┐
│                    API Externes                         │
│                                                         │
│  • CIRCL CVE API      (cve.circl.lu)                    │
│  • KEV API           (kevin.gtfkd.com)                  │
│  • NVD API           (services.nvd.nist.gov)            │
│  • FIRST EPSS API    (api.first.org)                    │
└─────────────────────────────────────────────────────────┘
```

### Architecture technique

L'application suit une architecture **client-serveur** simple :

#### Frontend (Client)
- **Pages PHP** : Génération du HTML côté serveur
- **JavaScript vanilla** : Logique applicative côté client
- **Bootstrap 5** : Framework CSS pour l'interface responsive
- **Chart.js** : Bibliothèque de graphiques pour les visualisations
- **Bootstrap Icons** : Icônes

#### Backend (Serveur)
- **PHP 8.1** : Langage serveur
- **Apache** : Serveur HTTP
- **API Proxies** : Couche d'abstraction pour éviter les problèmes CORS
  - Valide les entrées utilisateur
  - Appelle les API externes via cURL
  - Retourne les données au format JSON

#### Conteneurisation
- **Docker** : Isolation de l'environnement
- **Docker Compose** : Orchestration des services

### Flux de données

#### Recherche par ID CVE
```
User Input (CVE ID)
    → JavaScript validation (format CVE-YYYY-NNNN)
    → API Proxy (circl_proxy.php)
    → CIRCL CVE API
    → Response (CVE details)
    → Display in results.php
```

#### Recherche par mot-clé
```
User Input (keyword + time range)
    → JavaScript (keyword.js)
    → NVD API (direct fetch)
    → For each CVE:
        → KEV API via kev_proxy.php
        → EPSS API (direct fetch)
    → Data enrichment & aggregation
    → Statistical analysis
    → Chart rendering (Chart.js)
    → Display results
```

---

## 🔗 Sources de données / API

L'application interroge plusieurs sources de données publiques sur les vulnérabilités :

### 1. CIRCL CVE API
- **URL** : `https://cve.circl.lu/api/cve/{CVE_ID}`
- **Description** : Base de données CVE maintenue par le CIRCL (Computer Incident Response Center Luxembourg)
- **Utilisation** : Vérification de l'existence des CVE et récupération des détails
- **Format** : JSON
- **Documentation** : [https://cve.circl.lu/](https://cve.circl.lu/)
- **Limite de taux** : Non spécifiée
- **Exemple** :
  ```
  GET https://cve.circl.lu/api/cve/CVE-2024-1234
  ```

### 2. CISA KEV (Known Exploited Vulnerabilities)
- **URL** : `https://kevin.gtfkd.com/kev/{CVE_ID}`
- **Description** : Catalogue des vulnérabilités activement exploitées, maintenu par la CISA (Cybersecurity and Infrastructure Security Agency)
- **Utilisation** : Vérifier si une CVE est dans le catalogue KEV
- **Format** : JSON
- **API Wrapper** : kevin.gtfkd.com (interface simplifiée)
- **Exemple** :
  ```
  GET https://kevin.gtfkd.com/kev/exists?cve=CVE-2024-1234
  GET https://kevin.gtfkd.com/kev/CVE-2024-1234
  ```

### 3. NVD API (National Vulnerability Database)
- **URL** : `https://services.nvd.nist.gov/rest/json/cves/2.0`
- **Description** : Base de données nationale américaine sur les vulnérabilités, maintenue par le NIST
- **Utilisation** : Recherche de CVE par mot-clé et période
- **Format** : JSON
- **Documentation** : [https://nvd.nist.gov/developers/vulnerabilities](https://nvd.nist.gov/developers/vulnerabilities)
- **Limite de taux** : 5 requêtes / 30 secondes (sans clé API), 50 requêtes / 30 secondes (avec clé API)
- **Exemple** :
  ```
  GET https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=apache&pubStartDate=2024-01-01T00:00:00.000&pubEndDate=2024-12-31T23:59:59.999
  ```

### 4. FIRST EPSS API (Exploit Prediction Scoring System)
- **URL** : `https://api.first.org/data/v1/epss`
- **Description** : Scores de probabilité d'exploitation des vulnérabilités
- **Utilisation** : Enrichissement des CVE avec le score EPSS
- **Format** : JSON
- **Documentation** : [https://www.first.org/epss/api](https://www.first.org/epss/api)
- **Limite de taux** : Non restrictive
- **Exemple** :
  ```
  GET https://api.first.org/data/v1/epss?cve=CVE-2024-1234
  ```

### Proxies API

Pour éviter les problèmes CORS (Cross-Origin Resource Sharing), l'application utilise des proxies PHP côté serveur :

| Proxy | API cible | Fonction |
|-------|-----------|----------|
| `circl_proxy.php` | CIRCL CVE API | Récupération des détails CVE |
| `kev_proxy.php` | KEV API (exists) | Vérification présence dans KEV |
| `kev_details_proxy.php` | KEV API (details) | Détails KEV d'une CVE |

**Avantages des proxies** :
- ✅ Évite les erreurs CORS
- ✅ Validation des entrées côté serveur
- ✅ Possibilité de cache/logging
- ✅ Masquage des clés API (si nécessaire)
- ✅ Gestion d'erreurs standardisée

---

## 📁 Structure du projet

```
NDFCVE/
├── docker-compose.yaml          # Configuration Docker Compose
├── Dockerfile                   # Image Docker de l'application
├── LICENSE                      # Licence du projet
├── README.md                    # Documentation (ce fichier)
├── robots.txt                   # Règles pour les robots d'indexation
├── security.txt                 # Informations de sécurité
│
└── src/                         # Code source de l'application
    ├── index.php                # Page d'accueil - Recherche par ID CVE
    ├── keyword.php              # Page de recherche par mot-clé
    ├── results.php              # Page d'affichage des résultats
    │
    ├── api/                     # Proxies API
    │   ├── circl_proxy.php      # Proxy pour CIRCL CVE API
    │   ├── kev_proxy.php        # Proxy pour KEV existence check
    │   └── kev_details_proxy.php # Proxy pour KEV details
    │
    ├── assets/                  # Ressources statiques
    │   ├── favicon.svg          # Icône de l'application
    │   └── icons/
    │       └── favicon.svg
    │
    ├── js/                      # Scripts JavaScript
    │   ├── cveinfo.js           # Affichage des informations CVE
    │   ├── index.js             # Logique de la page d'accueil
    │   ├── keyword.js           # Logique de recherche par mot-clé
    │   ├── loader.js            # Animation de chargement
    │   ├── results.js           # Affichage des résultats
    │   ├── script.js            # Utilitaires généraux
    │   └── toaster.js           # Notifications toast
    │
    ├── styles/                  # Feuilles de style CSS
    │   ├── keyword.css          # Styles de la page keyword
    │   ├── loader.css           # Styles du loader
    │   ├── results.css          # Styles de la page résultats
    │   └── style.css            # Styles globaux
    │
    └── templates/               # Templates HTML
        ├── footer.html          # Pied de page
        ├── header.html          # En-tête
        └── modals.html          # Modales Bootstrap
```

---

## 🛠️ Technologies utilisées

### Backend
- **PHP 8.1** - Langage serveur
- **Apache** - Serveur HTTP
- **cURL** - Client HTTP pour appels API

### Frontend
- **HTML5** - Structure
- **CSS3** - Styles
- **JavaScript (ES6+)** - Logique applicative
- **Bootstrap 5.3.3** - Framework CSS
- **Bootstrap Icons** - Icônes
- **Chart.js 4.4.0** - Graphiques et visualisations

### DevOps
- **Docker** - Conteneurisation
- **Docker Compose** - Orchestration

### APIs externes
- CIRCL CVE API
- CISA KEV API
- NVD API (NIST)
- FIRST EPSS API

---

## 🤝 Contribuer

Les contributions sont les bienvenues ! Pour contribuer :

1. **Forkez** le projet
2. **Créez** une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. **Committez** vos changements (`git commit -m 'Add some AmazingFeature'`)
4. **Pushez** vers la branche (`git push origin feature/AmazingFeature`)
5. **Ouvrez** une Pull Request

### Suggestions d'améliorations
- [ ] Ajout d'une base de données pour le cache des résultats
- [ ] Support de l'authentification API NVD pour augmenter les limites de taux
- [ ] Export des résultats en CSV/JSON
- [ ] Historique des recherches
- [ ] Système de favoris
- [ ] Mode sombre
- [ ] Internationalisation (i18n)

---

## 📄 License

Ce projet est sous licence GNU General Public License v3.0. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 📧 Contact

Pour toute question ou suggestion, n'hésitez pas à ouvrir une issue sur GitHub.

---

## 🙏 Remerciements

- **CIRCL** pour leur API CVE
- **CISA** pour le catalogue KEV
- **NIST** pour la base de données NVD
- **FIRST** pour l'API EPSS
- La communauté open source pour les outils et frameworks utilisés
