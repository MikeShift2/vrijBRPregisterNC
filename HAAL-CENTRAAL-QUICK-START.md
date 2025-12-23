# 🚀 Haal Centraal Compliance - Quick Start Guide

**Voor:** Developers die willen starten met RvIG compliance implementatie  
**Tijd:** 4 weken tot 100% compliant  
**Huidige status:** 60% compliant

---

## ⚡ Start Direct

### Stap 1: Maak Feature Branch (2 min)

```bash
cd /Users/mikederuiter/Nextcloud
git checkout -b feature/rvig-informatieproducten
git push -u origin feature/rvig-informatieproducten
```

### Stap 2: Maak Service File (5 min)

```bash
# Maak directory aan
mkdir -p lib/Service

# Copy template
cat > lib/Service/InformatieproductenService.php << 'EOF'
<?php
namespace OCA\OpenRegister\Service;

class InformatieproductenService {
    
    public function berekenVoorletters($voornamen): string {
        // TODO: Implementeer voorletters berekening
        return '';
    }
    
    public function berekenLeeftijd(?string $geboortedatum): ?int {
        // TODO: Implementeer leeftijd berekening
        return null;
    }
    
    public function berekenAanschrijfwijze(array $persoon): string {
        // TODO: Implementeer aanschrijfwijze
        return '';
    }
    
    public function enrichPersoon(array $persoon): array {
        // TODO: Voeg informatieproducten toe
        return $persoon;
    }
}
EOF
```

### Stap 3: Implementeer Eerste Methode (30 min)

**Start met simpelste: voorletters**

```php
public function berekenVoorletters($voornamen): string {
    if (empty($voornamen)) {
        return '';
    }
    
    // Handle array
    if (is_array($voornamen)) {
        $voornamen = implode(' ', $voornamen);
    }
    
    // Split en maak voorletters
    $namen = explode(' ', trim($voornamen));
    $voorletters = [];
    
    foreach ($namen as $naam) {
        if (!empty($naam)) {
            $voorletters[] = strtoupper(substr($naam, 0, 1)) . '.';
        }
    }
    
    return implode('', $voorletters);
}
```

### Stap 4: Test Direct (5 min)

```bash
# Maak test file
cat > test-voorletters.php << 'EOF'
<?php
require_once 'lib/Service/InformatieproductenService.php';

use OCA\OpenRegister\Service\InformatieproductenService;

$service = new InformatieproductenService();

// Test cases
echo "Test 1: " . $service->berekenVoorletters('Jan') . "\n";           // Verwacht: J.
echo "Test 2: " . $service->berekenVoorletters('Jan Pieter') . "\n";    // Verwacht: J.P.
echo "Test 3: " . $service->berekenVoorletters(['Jan', 'Marie']) . "\n"; // Verwacht: J.M.
EOF

# Run test
docker exec nextcloud php /var/www/html/custom_apps/openregister/test-voorletters.php
```

---

## 🎯 Prioriteiten Matrix

### Week 1: Start Hier 🔴

**Critical Path:**

```
1. InformatieproductenService     [2 dagen]
   └─ berekenVoorletters()         [4 uur]
   └─ berekenLeeftijd()            [2 uur]
   └─ berekenAanschrijfwijze()     [6 uur]
   └─ berekenAanhef()              [4 uur]
   └─ berekenAdresregels()         [6 uur]
   └─ Unit tests                   [8 uur]

2. Controller Integratie          [2 dagen]
   └─ Dependency injection         [2 uur]
   └─ enrichPersoon() integreren   [4 uur]
   └─ API tests                    [6 uur]

3. Validatie                      [1 dag]
   └─ Test alle BSN's              [4 uur]
   └─ Response verificatie         [4 uur]
```

**Output:** Informatieproducten in API responses ✅

---

## 📋 Minimale Viable Product (MVP)

**Als tijd beperkt is, doe dit eerst:**

### MVP Scope (1 week)

**Must have:**
1. ✅ Voorletters
2. ✅ Leeftijd  
3. ✅ Aanschrijfwijze

**Skip voor MVP:**
- ⏭️ Gezag (complex)
- ⏭️ Bewoning API (aparte feature)
- ⏭️ RNI (niche use case)
- ⏭️ Headers (details)

**MVP Impact:** 60% → 80% compliance in 1 week

---

## 🧪 Test-Driven Development

### Write Test First

**1. Test schrijven (5 min):**

```php
// tests/Unit/Service/InformatieproductenServiceTest.php
public function testBerekenVoorletters() {
    $service = new InformatieproductenService();
    
    // Test basic
    $this->assertEquals('J.', $service->berekenVoorletters('Jan'));
    
    // Test multiple
    $this->assertEquals('J.P.M.', $service->berekenVoorletters('Jan Pieter Marie'));
}
```

**2. Run test (zie het falen):**

```bash
docker exec nextcloud php vendor/bin/phpunit tests/Unit/Service/InformatieproductenServiceTest.php::testBerekenVoorletters
```

**3. Implementeer totdat test slaagt**

**4. Refactor & herhaal**

---

## 🔧 Common Issues & Solutions

### Issue 1: Dependency Injection Werkt Niet

**Symptoom:**
```
Call to a member function berekenVoorletters() on null
```

**Oplossing:**

```php
// lib/AppInfo/Application.php
public function register(IRegistrationContext $context): void {
    // Registreer service
    $context->registerService(InformatieproductenService::class, function($c) {
        return new InformatieproductenService();
    });
}
```

---

### Issue 2: Voorletters Niet in Response

**Symptoom:**
```json
{
  "naam": {
    "voornamen": "Jan"
    // Geen voorletters
  }
}
```

**Debug:**

```php
// In transformToHaalCentraal(), add debug:
error_log("BEFORE enrich: " . json_encode($result));
$result = $this->informatieproductenService->enrichPersoon($result);
error_log("AFTER enrich: " . json_encode($result));
```

**Check logs:**

```bash
docker logs nextcloud 2>&1 | grep "BEFORE enrich" | tail -5
```

---

### Issue 3: Cache Not Invalidating

**Symptoom:** Oude data blijft terugkomen na wijzigingen

**Oplossing:**

```php
// Add cache clearing bij data updates
public function updatePersoon(string $bsn, array $data): void {
    // Update data
    $this->db->update($data);
    
    // Clear cache
    if ($this->cacheService) {
        $this->cacheService->delete('informatieproducten_' . $bsn);
    }
}
```

---

## 📚 Code Templates

### Template 1: Nieuwe Informatieproduct Methode

```php
/**
 * Bereken [NAAM] volgens RvIG specificatie
 * 
 * Referentie: https://developer.rvig.nl/brp-api/personen/documentatie/informatieproducten/[naam]/
 * 
 * Regels:
 * - [Regel 1]
 * - [Regel 2]
 * 
 * @param array $persoon Persoon object
 * @return mixed Berekende waarde
 */
public function bereken[Naam](array $persoon) {
    // Input validatie
    if (empty($persoon['veld'])) {
        return null;
    }
    
    // Berekening
    $result = // ... logica ...
    
    return $result;
}
```

### Template 2: Unit Test

```php
public function testBereken[Naam]() {
    $service = new InformatieproductenService();
    
    // Test happy path
    $persoon = ['veld' => 'waarde'];
    $result = $service->bereken[Naam]($persoon);
    $this->assertEquals('verwacht', $result);
    
    // Test edge case: empty
    $persoon = ['veld' => ''];
    $result = $service->bereken[Naam]($persoon);
    $this->assertNull($result);
    
    // Test edge case: null
    $persoon = [];
    $result = $service->bereken[Naam]($persoon);
    $this->assertNull($result);
}
```

### Template 3: Integration Test

```php
public function testApiReturns[Product]() {
    $response = $this->get('/ingeschrevenpersonen?bsn=168149291');
    $persoon = $response['data']['_embedded']['ingeschrevenpersonen'][0];
    
    // Verify informatieproduct is present
    $this->assertArrayHasKey('[veld]', $persoon);
    $this->assertNotEmpty($persoon['[veld]']);
    
    // Verify format
    $this->assertIsString($persoon['[veld]']);
    $this->assertMatchesRegularExpression('/[pattern]/', $persoon['[veld]']);
}
```

---

## 🎯 Daily Standup Format

**Elke dag:**

```markdown
## Standup [Datum]

### Gisteren gedaan:
- [ ] Task 1
- [ ] Task 2

### Vandaag plan:
- [ ] Task 3
- [ ] Task 4

### Blockers:
- [ ] Blocker 1 (indien van toepassing)

### Compliance score:
- Was: XX%
- Nu: YY%
- Delta: +ZZ%
```

---

## 📞 Quick Commands

### Development

```bash
# Start containers
docker-compose up -d

# Watch logs
docker logs -f nextcloud

# Run specific test
docker exec nextcloud php vendor/bin/phpunit tests/Unit/Service/InformatieproductenServiceTest.php

# Check compliance
./test-rvig-compliance.sh

# Clear cache
docker exec nextcloud php occ cache:clear
```

### Testing

```bash
# Test informatieproducten
curl -u admin:admin "http://localhost:8080/apps/openregister/ingeschrevenpersonen?bsn=168149291" | jq '.._embedded.ingeschrevenpersonen[0].naam.voorletters'

# Test bewoning
curl -u admin:admin "http://localhost:8080/apps/openregister/adressen/0363200000218705/bewoning?peildatum=2024-01-01" | jq

# Test RNI
curl -u admin:admin "http://localhost:8080/apps/openregister/ingeschrevenpersonen?inclusiefRni=true&_limit=5" | jq
```

### Debugging

```bash
# Enable debug mode
docker exec nextcloud php occ config:system:set debug --value=true --type=boolean

# Tail PHP errors
docker exec nextcloud tail -f /var/www/html/data/nextcloud.log

# Check database
docker exec nextcloud-db mariadb -u nextcloud_user -pnextcloud_secure_pass_2024 nextcloud
```

---

## 🏁 Definition of Done

**Elke feature is "done" als:**

- [ ] Code geïmplementeerd
- [ ] Unit tests geschreven (>90% coverage)
- [ ] Integratie test geschreven
- [ ] Code review passed
- [ ] Documentatie geüpdatet
- [ ] Manual test geslaagd
- [ ] Merge naar feature branch
- [ ] Compliance score geüpdatet

---

## 📊 Progress Tracking

**Update deze tabel elke vrijdag:**

| Week | Datum | Compliance | Features Done | Status |
|------|-------|-----------|---------------|---------|
| 0 | 2025-01-23 | 60% | Nested objects | ✅ Basis |
| 1 | [TBD] | 75% | Informatieproducten kern | 🔄 In progress |
| 2 | [TBD] | 85% | Gezag & Caching | ⏸️ Pending |
| 3 | [TBD] | 95% | Bewoning & RNI | ⏸️ Pending |
| 4 | [TBD] | 100% | Finalisering | ⏸️ Pending |

---

## 🎉 Launch Checklist

**Final checklist voor Go-Live:**

### Functioneel
- [ ] Alle informatieproducten werken
- [ ] Bewoning API getest
- [ ] RNI filtering werkt
- [ ] Alle endpoints 200 OK
- [ ] Error responses correct

### Kwaliteit
- [ ] PHPUnit tests 100% pass
- [ ] Compliance tests 100% pass
- [ ] Code coverage >90%
- [ ] Performance benchmarks OK

### Documentatie
- [ ] API docs compleet
- [ ] Changelog geschreven
- [ ] Migration guide gemaakt
- [ ] README geüpdatet

### Operationeel
- [ ] Monitoring configured
- [ ] Alerts setup
- [ ] Rollback plan ready
- [ ] Team trained

**Als alle checkboxes ✅ → GO FOR LAUNCH! 🚀**

---

## 📞 Need Help?

**Stuck? Check:**
1. 📄 `HAAL-CENTRAAL-COMPLIANCE-PLAN.md` - Gedetailleerd plan
2. 📄 `HAAL-CENTRAAL-ROADMAP.md` - Visuele roadmap
3. 📄 `RVIG-BRP-API-COMPLIANCE-CHECK.md` - Gap analyse
4. 🌐 https://developer.rvig.nl/brp-api/overview/ - RvIG docs

**Contact:**
- RvIG Support: info@rvig.nl
- Developer Portal: https://developer.rvig.nl/

---

**Status:** ✅ Ready to Start  
**First Task:** Implementeer `berekenVoorletters()`  
**Expected Duration:** 4 weken tot 100% compliant
