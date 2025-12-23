# 🗺️ Haal Centraal Compliance - Visuele Roadmap

**Doel:** 60% → 100% RvIG BRP API Compliant  
**Tijdlijn:** 4 weken

---

## 📈 Compliance Progressie

```
Start     Week 1    Week 2    Week 3    Week 4    Eind
 60% ━━━━━━ 75% ━━━━━━ 85% ━━━━━━ 95% ━━━━━━ 100%
  │          │          │          │          │
  │          │          │          │          └─ Testing & Docs
  │          │          │          └─ Bewoning API & RNI
  │          │          └─ Gezag & Caching
  │          └─ Basis Informatieproducten
  └─ Huidige Status
```

---

## 🎯 Week-by-Week Overzicht

### WEEK 1: Informatieproducten Kern 🔴 **KRITIEK**

**Compliance Impact:** +15% (60% → 75%)

```
┌─────────────────────────────────────────────────┐
│ DAG 1-2: Service Layer                          │
├─────────────────────────────────────────────────┤
│ • InformatieproductenService.php aanmaken       │
│ • berekenVoorletters() implementeren            │
│ • berekenLeeftijd() implementeren               │
│ • Unit tests schrijven                          │
├─────────────────────────────────────────────────┤
│ Deliverable: Service met 2 methodes + tests    │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ DAG 3-4: Adressering                            │
├─────────────────────────────────────────────────┤
│ • berekenAanschrijfwijze() implementeren        │
│ • berekenAanhef() implementeren                 │
│ • berekenGebruikInLopendeTekst() implementeren  │
│ • berekenAdresregels() implementeren            │
│ • berekenVolledigeNaam() implementeren          │
├─────────────────────────────────────────────────┤
│ Deliverable: Adressering compleet + tests      │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ DAG 5: Integratie                               │
├─────────────────────────────────────────────────┤
│ • Service injecteren in controller              │
│ • enrichPersoon() toevoegen aan responses       │
│ • API testen met test-informatieproducten.sh    │
│ • Verifiëren dat alle velden in response zitten │
├─────────────────────────────────────────────────┤
│ Deliverable: Informatieproducten in API ✅      │
└─────────────────────────────────────────────────┘
```

**Output Week 1:**
```json
{
  "burgerservicenummer": "168149291",
  "naam": {
    "voornamen": "Jan",
    "voorletters": "J.",           // ✅ NIEUW
    "volledigeNaam": "J. Jansen",  // ✅ NIEUW
    "geslachtsnaam": "Jansen"
  },
  "leeftijd": 42,                  // ✅ NIEUW
  "adressering": {                 // ✅ NIEUW
    "aanschrijfwijze": "De heer J. Jansen",
    "aanhef": "Geachte heer Jansen",
    "gebruikInLopendeTekst": "de heer Jansen",
    "adresregel1": "De heer J. Jansen",
    "adresregel2": "Hoofdstraat 123",
    "adresregel3": "1234AB  AMSTERDAM"
  }
}
```

---

### WEEK 2: Uitbreiden & Optimaliseren 🟡 **BELANGRIJK**

**Compliance Impact:** +10% (75% → 85%)

```
┌─────────────────────────────────────────────────┐
│ DAG 6-7: Gezag Informatieproduct                │
├─────────────────────────────────────────────────┤
│ • berekenGezag() implementeren                  │
│ • Minderjarigen detectie                        │
│ • Gezagsrelaties uit gezag_ax tabel             │
│ • Test met minderjarigen                        │
├─────────────────────────────────────────────────┤
│ Deliverable: Gezag informatieproduct ✅         │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ DAG 8-9: Performance & Caching                  │
├─────────────────────────────────────────────────┤
│ • Cache strategie voor informatieproducten      │
│ • Cache invalidatie implementeren               │
│ • Performance metingen (voor/na)                │
│ • Load testing (100 req/sec)                    │
├─────────────────────────────────────────────────┤
│ Deliverable: <200ms response tijd (P95) ✅      │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ DAG 10: Testing & Code Review                   │
├─────────────────────────────────────────────────┤
│ • Integratie tests schrijven                    │
│ • Code coverage check (target: >90%)            │
│ • Peer review                                   │
│ • Refactoring waar nodig                        │
├─────────────────────────────────────────────────┤
│ Deliverable: Production ready code ✅           │
└─────────────────────────────────────────────────┘
```

**Output Week 2:**
```json
{
  "burgerservicenummer": "999999011",
  "naam": {...},
  "leeftijd": 8,
  "adressering": {...},
  "gezag": {                       // ✅ NIEUW (voor minderjarigen)
    "gezagsrelaties": [
      {
        "type": "ouderlijkGezag",
        "minderjarige": {
          "burgerservicenummer": "999999011"
        },
        "ouder": {
          "burgerservicenummer": "999999012"
        }
      }
    ]
  }
}
```

---

### WEEK 3: Bewoning API & RNI 🟢 **NICE TO HAVE**

**Compliance Impact:** +10% (85% → 95%)

```
┌─────────────────────────────────────────────────┐
│ DAG 11-13: Bewoning API                         │
├─────────────────────────────────────────────────┤
│ • BewoningController.php aanmaken               │
│ • getBewonersPeildatum() implementeren          │
│ • getBewonersPeriode() implementeren            │
│ • Routes registreren                            │
│ • PostgreSQL vb_ax queries                      │
│ • Response format volgens RvIG                  │
├─────────────────────────────────────────────────┤
│ Deliverable: Bewoning API werkend ✅            │
└─────────────────────────────────────────────────┘

Endpoint voorbeeld:
GET /adressen/0363200000218705/bewoning?peildatum=2024-01-01

Response:
{
  "_embedded": {
    "bewoning": [
      {
        "periode": {
          "datumVan": "2020-01-01",
          "datumTot": null
        },
        "bewoners": [
          {
            "burgerservicenummer": "168149291",
            "naam": {...}
          },
          {
            "burgerservicenummer": "216007574",
            "naam": {...}
          }
        ]
      }
    ]
  }
}

┌─────────────────────────────────────────────────┐
│ DAG 14: RNI Ontsluiting                         │
├─────────────────────────────────────────────────┤
│ • Parameter inclusiefRni=true toevoegen         │
│ • RNI filtering in queries                      │
│ • RNI data uit rni_ax tabel ophalen             │
│ • RNI vlag in persoon response                  │
├─────────────────────────────────────────────────┤
│ Deliverable: RNI support ✅                     │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ DAG 15: Testing Week 3                          │
├─────────────────────────────────────────────────┤
│ • test-bewoning.sh schrijven en runnen          │
│ • test-rni.sh schrijven en runnen               │
│ • Performance tests                             │
│ • Bug fixes                                     │
├─────────────────────────────────────────────────┤
│ Deliverable: Alle tests groen ✅                │
└─────────────────────────────────────────────────┘
```

---

### WEEK 4: Finalisering 🔵 **POLISH**

**Compliance Impact:** +5% (95% → 100%)

```
┌─────────────────────────────────────────────────┐
│ DAG 16-17: Query Parameters                     │
├─────────────────────────────────────────────────┤
│ • burgerservicenummer parameter (ipv bsn)       │
│ • Backward compatibility behouden               │
│ • Frontend updaten naar nieuwe namen            │
│ • Deprecation warnings voor oude parameters     │
├─────────────────────────────────────────────────┤
│ Deliverable: Moderne parameters ✅              │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ DAG 18: Headers & Error Handling                │
├─────────────────────────────────────────────────┤
│ • Content-Type: application/hal+json            │
│ • X-Correlation-ID support                      │
│ • RFC 7807 Problem Details                      │
│ • Error response tests                          │
├─────────────────────────────────────────────────┤
│ Deliverable: Headers & errors RvIG compliant ✅ │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ DAG 19-20: Testing & Launch                     │
├─────────────────────────────────────────────────┤
│ • RvIG compliance test suite volledig           │
│ • Alle tests 100% groen                         │
│ • Documentatie compleet                         │
│ • Changelog schrijven                           │
│ • Stakeholder demo                              │
│ • Production deployment prep                    │
├─────────────────────────────────────────────────┤
│ Deliverable: 100% RvIG Compliant API ✅         │
└─────────────────────────────────────────────────┘
```

---

## 🏆 Milestone Overzicht

```
┌──────────┬──────────────────────────┬───────────┐
│ Milestone│ Feature                  │ Compliance│
├──────────┼──────────────────────────┼───────────┤
│ START    │ Nested objects           │    60%    │
│          │ Basis endpoints          │           │
├──────────┼──────────────────────────┼───────────┤
│ M1       │ + Voorletters & Leeftijd │    70%    │
│ (Week 1) │ + Aanschrijfwijze        │           │
│          │ + Aanhef & Adresregels   │    75%    │
├──────────┼──────────────────────────┼───────────┤
│ M2       │ + Gezag product          │    80%    │
│ (Week 2) │ + Caching                │           │
│          │ + Performance            │    85%    │
├──────────┼──────────────────────────┼───────────┤
│ M3       │ + Bewoning API           │    90%    │
│ (Week 3) │ + RNI ontsluiting        │    95%    │
├──────────┼──────────────────────────┼───────────┤
│ M4       │ + Query params           │    98%    │
│ (Week 4) │ + Headers & Errors       │           │
│ LAUNCH   │ + Docs & Tests           │   100%    │
└──────────┴──────────────────────────┴───────────┘
```

---

## 🎯 Critical Path

**Blokkers (kunnen niet parallel):**

```
Week 1 Dag 1-2: Service Layer
       ↓
Week 1 Dag 3-4: Controller Integratie
       ↓
Week 1 Dag 5: Testing
       ↓
Week 2 Dag 6-9: Uitbreidingen
       ↓
Week 3 Dag 11-15: Bewoning & RNI
       ↓
Week 4 Dag 16-20: Finalisering
```

**Parallel mogelijk:**
- Week 2: Gezag (Dag 6-7) || Caching (Dag 8-9)
- Week 3: Bewoning (Dag 11-13) || RNI (Dag 14)
- Week 4: Parameters (Dag 16-17) || Headers (Dag 18)

---

## 📊 Effort Breakdown

### Per Component

| Component | Complexity | Effort | Priority |
|-----------|-----------|---------|----------|
| **Voorletters** | 🟢 Laag | 4 uur | 🔴 Hoog |
| **Leeftijd** | 🟢 Laag | 2 uur | 🔴 Hoog |
| **Aanschrijfwijze** | 🟡 Medium | 6 uur | 🔴 Hoog |
| **Aanhef** | 🟡 Medium | 4 uur | 🔴 Hoog |
| **Adresregels** | 🟡 Medium | 6 uur | 🔴 Hoog |
| **Volledige naam** | 🟡 Medium | 4 uur | 🔴 Hoog |
| **Gezag** | 🔴 Hoog | 12 uur | 🟡 Medium |
| **Caching** | 🟡 Medium | 8 uur | 🟡 Medium |
| **Bewoning API** | 🔴 Hoog | 20 uur | 🟡 Medium |
| **RNI** | 🟡 Medium | 8 uur | 🟢 Laag |
| **Parameters** | 🟢 Laag | 4 uur | 🟢 Laag |
| **Headers** | 🟢 Laag | 4 uur | 🟢 Laag |
| **Testing** | 🟡 Medium | 16 uur | 🔴 Hoog |
| **Docs** | 🟢 Laag | 8 uur | 🔴 Hoog |

**Totaal:** ~106 uur = ~13 dagen (met buffer: 20 dagen = 4 weken)

---

## 🚦 Risk Matrix

### Hoog Risico 🔴

**1. Gezag Berekening Complexiteit**
- **Risico:** Business logic complex, edge cases
- **Mitigatie:** Start met simpele implementatie, itereer
- **Impact:** 10% compliance
- **Owner:** Senior Developer

**2. Bewoning API Performance**
- **Risico:** Historische queries kunnen traag zijn
- **Mitigatie:** Indexen toevoegen, caching
- **Impact:** API response tijd
- **Owner:** Senior Developer + DBA

### Medium Risico 🟡

**3. RNI Data Kwaliteit**
- **Risico:** RNI tabel mogelijk incomplete
- **Mitigatie:** Data audit eerst, fallbacks
- **Impact:** 5% compliance
- **Owner:** Data Engineer

**4. Caching Invalidatie**
- **Risico:** Stale data bij updates
- **Mitigatie:** Event-driven cache clearing
- **Impact:** Data freshness
- **Owner:** Senior Developer

### Laag Risico 🟢

**5. Header Changes**
- **Risico:** Minimaal, backward compatible
- **Mitigatie:** N/A
- **Impact:** 1% compliance
- **Owner:** Any Developer

---

## ✅ Pre-Implementation Checklist

### Technisch

- [x] Database bevat alle benodigde data (probev)
- [x] Nested objects implementatie compleet
- [x] Veldnamen correct
- [x] Backend queries werken
- [ ] Test data set beschikbaar
- [ ] Development environment setup
- [ ] CI/CD pipeline ready

### Organisatorisch

- [ ] Stakeholder buy-in
- [ ] Resource allocatie (1-2 developers)
- [ ] Timeline approved
- [ ] Testing capacity reserved
- [ ] Acceptance criteria defined

### Documentatie

- [x] RvIG spec doorgenomen
- [x] Gap analyse compleet
- [x] Implementatie plan goedgekeurd
- [ ] API design reviews scheduled
- [ ] Technical writer beschikbaar

---

## 🎯 Success Metrics

### Week 1 Success Criteria

```
✅ InformatieproductenService compleet
✅ 6 basis informatieproducten werkend
✅ Unit tests >90% coverage
✅ API responses bevatten nieuwe velden
✅ Compliance: 75%
```

### Week 2 Success Criteria

```
✅ Gezag informatieproduct werkend
✅ Caching geïmplementeerd
✅ Response tijd <200ms (P95)
✅ Cache hit rate >80%
✅ Compliance: 85%
```

### Week 3 Success Criteria

```
✅ Bewoning API werkend
✅ RNI filtering werkend
✅ Alle endpoints testen groen
✅ Performance acceptabel
✅ Compliance: 95%
```

### Week 4 Success Criteria

```
✅ Alle parameters modern + backward compat
✅ Headers HAL JSON compliant
✅ Error responses RFC 7807
✅ RvIG compliance test 100%
✅ Compliance: 100% 🎉
```

---

## 📞 Escalatie Matrix

### Blockers

**Issue escalatie flow:**

```
Developer
   ↓ (blocker >4 uur)
Tech Lead
   ↓ (blocker >1 dag)
Architect
   ↓ (beslissing nodig)
Product Owner
```

**Contacten:**
- Tech Lead: [naam]
- Architect: [naam]
- Product Owner: [naam]
- RvIG Support: info@rvig.nl

---

## 🎉 Launch Criteria

**Voor production deployment:**

### Functioneel ✅
- [x] Nested objects werken (GEDAAN)
- [ ] Alle informatieproducten geïmplementeerd
- [ ] Bewoning API werkend
- [ ] RNI ontsluiting werkend

### Kwaliteit ✅
- [ ] Unit tests >90% coverage
- [ ] Integratie tests 100% pass
- [ ] RvIG compliance tests 100% pass
- [ ] Performance benchmarks gehaald

### Documentatie ✅
- [ ] API documentatie compleet
- [ ] Developer guides geschreven
- [ ] Changelog gepubliceerd
- [ ] Migration guide (voor consumers)

### Operationeel ✅
- [ ] Monitoring dashboards
- [ ] Alerting geconfigureerd
- [ ] Rollback plan documented
- [ ] Runbook geschreven

---

## 📈 Compliance Dashboard

**Real-time tracking:**

```
╔══════════════════════════════════════════════╗
║  RvIG BRP API Compliance Dashboard           ║
╠══════════════════════════════════════════════╣
║                                              ║
║  Current:  [████████████░░░░░░░░] 60%       ║
║  Target:   [████████████████████] 100%      ║
║                                              ║
║  ┌────────────────────────────────────────┐ ║
║  │ Personen API        [████████] 100%    │ ║
║  │ Informatieproducten [░░░░░░░░]   0%    │ ║
║  │ Bewoning API        [░░░░░░░░]   0%    │ ║
║  │ Verblijfplaats      [██████░░]  70%    │ ║
║  │ RNI                 [░░░░░░░░]   0%    │ ║
║  │ Headers             [████░░░░]  50%    │ ║
║  └────────────────────────────────────────┘ ║
║                                              ║
║  Status: ⚠️  IN PROGRESS                     ║
║  ETA: 4 weeks                                ║
║  Blockers: 0                                 ║
║                                              ║
╚══════════════════════════════════════════════╝
```

**Update elke week:**
- [ ] Week 1 → 75%
- [ ] Week 2 → 85%
- [ ] Week 3 → 95%
- [ ] Week 4 → 100% ✅

---

## 🎓 Learning Resources

**Voor implementatie team:**

1. **RvIG Documentatie:**
   - 📚 Overzicht: https://developer.rvig.nl/brp-api/overview/
   - 📚 Informatieproducten: https://developer.rvig.nl/brp-api/personen/documentatie/informatieproducten/
   - 📚 Bewoning: https://developer.rvig.nl/brp-api/bewoning/specificatie/

2. **HAL JSON:**
   - 📚 Spec: https://datatracker.ietf.org/doc/html/draft-kelly-json-hal
   - 📚 Guide: https://stateless.group/hal_specification.html

3. **RFC 7807 Problem Details:**
   - 📚 Spec: https://datatracker.ietf.org/doc/html/rfc7807

4. **Interne Docs:**
   - 📄 `NESTED-OBJECTS-IMPLEMENTATIE-COMPLEET.md`
   - 📄 `RVIG-BRP-API-COMPLIANCE-CHECK.md`
   - 📄 `OPENREGISTER-BRP-FINALE-STATUS.md`

---

**Status:** 📋 **READY FOR IMPLEMENTATION**  
**Volgende stap:** Stakeholder approval & resource allocatie  
**Start:** Na approval  
**Launch:** Start + 4 weken
