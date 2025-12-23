# ZGW Documenten Implementatie ✅

**Datum:** 2025-01-27  
**Status:** ✅ **VOLTOOID**

---

## ✅ Wat is Geïmplementeerd

### 1. Schema Configuratie ✅

- ✅ **Schema ID 23 (Documenten)** - Aangemaakt
  - Document ID, zaak ID, document type, titel, beschrijving
  - Bestandsnaam, bestandspad, bestandsgrootte, MIME type
  - Auteur, creatiedatum, versie

### 2. Register Aangemaakt ✅

- ✅ **Register ID 6 (Documenten)** - Aangemaakt en gekoppeld aan Schema ID 23

### 3. ZGW Document Controller ✅

#### ZgwDocumentController.php
- ✅ `getDocumenten()` - Lijst alle documenten met filters
- ✅ `getDocument()` - Specifiek document ophalen
- ✅ `createDocument()` - Nieuw document aanmaken en uploaden naar Nextcloud Files
- ✅ `deleteDocument()` - Document verwijderen (ook uit Nextcloud Files)
- ✅ `downloadDocument()` - Download document bestand
- ✅ Nextcloud Files integratie
- ✅ Automatische folder structuur (ZGW Documenten/Zaak-{id}/)
- ✅ Unieke bestandsnamen met timestamp

---

## 🎯 Belangrijkste Features

### Nextcloud Files Integratie ✅

**Folder Structuur:**
```
Nextcloud Files/
└── ZGW Documenten/
    ├── Zaak-{zaakId}/
    │   ├── document1_20250127.pdf
    │   └── document2_20250127.docx
    └── document3_20250127.pdf (zonder zaak)
```

**Voordelen:**
- ✅ Documenten zijn direct beschikbaar in Nextcloud Files
- ✅ Gebruikers kunnen documenten bekijken via Nextcloud UI
- ✅ Versiebeheer via Nextcloud
- ✅ Sharing mogelijk via Nextcloud
- ✅ Volledige integratie met Nextcloud ecosysteem

---

## 📋 API Endpoints

### Documenten Endpoints

**GET** `/apps/openregister/zgw/documenten`
- Lijst alle documenten
- Query parameters: `zaakId`, `documentType`, `page`, `page_size`

**GET** `/apps/openregister/zgw/documenten/{documentId}`
- Specifiek document ophalen (metadata)

**GET** `/apps/openregister/zgw/documenten/{documentId}/download`
- Download document bestand

**POST** `/apps/openregister/zgw/documenten`
- Nieuw document aanmaken en uploaden
- Form data: `bestand` (file), `titel`, `document_type`, `zaak_id`, etc.

**DELETE** `/apps/openregister/zgw/documenten/{documentId}`
- Document verwijderen (ook uit Nextcloud Files)

---

## 🧪 Testen

### Test Document Uploaden

```bash
# Upload document
curl -X POST -u admin:password \
  -F "bestand=@/path/to/document.pdf" \
  -F "titel=Test Document" \
  -F "document_type=bijlage" \
  -F "zaak_id=zaak-uuid" \
  -F "beschrijving=Test document voor ZGW" \
  "http://localhost:8080/apps/openregister/zgw/documenten"

# Lijst documenten
curl -u admin:password \
  "http://localhost:8080/apps/openregister/zgw/documenten?zaakId=zaak-uuid"

# Download document
curl -u admin:password \
  "http://localhost:8080/apps/openregister/zgw/documenten/{documentId}/download" \
  -o document.pdf
```

---

## 📊 Implementatie Status

| Component | Status | Percentage |
|-----------|--------|------------|
| Schema Configuratie | ✅ Voltooid | 100% |
| Register Aanmaken | ✅ Voltooid | 100% |
| ZgwDocumentController | ✅ Voltooid | 100% |
| Nextcloud Files Integratie | ✅ Voltooid | 100% |
| Routes Configuratie | ✅ Voltooid | 100% |

**Totaal:** **100% Voltooid** ✅

---

## 🎉 Belangrijkste Prestaties

✅ **Documenten in Nextcloud Files** - Direct beschikbaar in Nextcloud UI  
✅ **Automatische Folder Structuur** - Georganiseerd per zaak  
✅ **Volledige CRUD** - Aanmaken, ophalen, downloaden, verwijderen  
✅ **Metadata Management** - Document metadata in Open Register  
✅ **Bestandsbeheer** - Unieke bestandsnamen, MIME type detectie  

---

## 🔄 Integratie met Zaken

Documenten kunnen worden gekoppeld aan zaken via:
- `zaak_id` - UUID van de zaak
- `zaak_identificatie` - Identificatie van de zaak

Documenten worden automatisch georganiseerd in folders per zaak:
- `ZGW Documenten/Zaak-{zaakId}/`

---

## 📝 Volgende Stappen (Optioneel)

1. **Versiebeheer** - Meerdere versies van hetzelfde document
2. **Metadata Extractie** - Automatisch metadata uit documenten halen
3. **OCR** - Tekst extractie uit PDF's en afbeeldingen
4. **Preview** - Document preview in API response
5. **Sharing** - Documenten delen via Nextcloud sharing

---

**Document versie:** 1.0  
**Laatste update:** 2025-01-27  
**Status:** ✅ Implementatie voltooid!







