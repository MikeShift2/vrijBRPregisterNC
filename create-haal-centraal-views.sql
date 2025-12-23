-- SQL Views voor Haal Centraal-compliant data uit probev-schema
-- Deze views denormaliseren de genormaliseerde probev-data voor gebruik in Open Register

-- ============================================================================
-- View: v_inw_ax_haal_centraal
-- Denormaliseert inw_ax tabel naar Haal Centraal-formaat
-- ============================================================================
CREATE OR REPLACE VIEW probev.v_inw_ax_haal_centraal AS
SELECT 
    -- Identificatie
    i.bsn,
    i.pl_id,
    
    -- Naamgegevens (denormaliseren via joins)
    COALESCE(v.voorn, '') as voornamen,
    COALESCE(n.naam, '') as geslachtsnaam,
    COALESCE(voorv.voorv, '') as voorvoegsel,
    
    -- Geboortegegevens
    CASE 
        WHEN i.d_geb IS NOT NULL AND LENGTH(i.d_geb::text) = 8 THEN
            TO_CHAR(TO_DATE(i.d_geb::text, 'YYYYMMDD'), 'YYYY-MM-DD')
        ELSE NULL
    END as geboortedatum,
    COALESCE(p_geb.plaats, '') as geboorteplaats,
    COALESCE(TRIM(l_geb.zoekarg), '') as geboorteland_code,
    COALESCE(TRIM(l_geb.land), '') as geboorteland_omschrijving,
    
    -- Geslacht transformatie (V/M/O → vrouw/man/onbekend)
    CASE i.geslacht
        WHEN 'V' THEN 'vrouw'
        WHEN 'M' THEN 'man'
        WHEN 'O' THEN 'onbekend'
        ELSE 'onbekend'
    END as geslachtsaanduiding,
    
    -- A-nummer (ophalen uit pl tabel via pl_id - kolom heet mogelijk anders)
    NULL::text as aNummer,  -- TODO: Bepaal juiste kolom voor A-nummer
    
    -- Metadata voor filtering
    i.ax,
    i.hist
    
FROM probev.inw_ax i
LEFT JOIN probev.voorn v ON v.c_voorn = i.c_voorn
LEFT JOIN probev.naam n ON n.c_naam = i.c_naam
LEFT JOIN probev.voorv voorv ON voorv.c_voorv = i.c_voorv
LEFT JOIN probev.plaats p_geb ON p_geb.c_plaats = i.p_geb
LEFT JOIN probev.land l_geb ON l_geb.c_land = i.l_geb
WHERE i.ax = 'A' AND i.hist = 'A';

-- ============================================================================
-- View: v_vb_ax_haal_centraal
-- Denormaliseert vb_ax (verblijfplaats) tabel naar Haal Centraal-formaat
-- ============================================================================
CREATE OR REPLACE VIEW probev.v_vb_ax_haal_centraal AS
SELECT 
    vb.pl_id,
    -- BSN ophalen via pl_id join met inw_ax
    (SELECT i.bsn FROM probev.inw_ax i WHERE i.pl_id = vb.pl_id AND i.ax = 'A' AND i.hist = 'A' LIMIT 1) as bsn,
    COALESCE(s.straat, '') as verblijfplaats_straatnaam,
    COALESCE(vb.hnr::text, '') as verblijfplaats_huisnummer,
    COALESCE(
        CASE 
            WHEN vb.hnr_l != ' ' THEN vb.hnr_l
            WHEN vb.hnr_t != ' ' THEN vb.hnr_t
            WHEN vb.hnr_a != ' ' THEN vb.hnr_a
            ELSE ''
        END, 
        ''
    ) as verblijfplaats_huisnummertoevoeging,
    COALESCE(TRIM(vb.pc), '') as verblijfplaats_postcode,
    COALESCE(p.plaats, '') as verblijfplaats_woonplaats,
    COALESCE(TRIM(l.zoekarg), '') as verblijfplaats_land_code,
    COALESCE(TRIM(l.land), '') as verblijfplaats_land_omschrijving,
    vb.ax,
    vb.hist
FROM probev.vb_ax vb
LEFT JOIN probev.straat s ON s.c_straat = vb.c_straat
LEFT JOIN probev.plaats p ON p.c_plaats = vb.c_wpl
LEFT JOIN probev.land l ON l.c_land = vb.l_vertrek
WHERE vb.ax = 'A' AND vb.hist = 'A';

-- ============================================================================
-- View: v_personen_compleet_haal_centraal
-- Combineert persoon- en adresgegevens in één view
-- ============================================================================
CREATE OR REPLACE VIEW probev.v_personen_compleet_haal_centraal AS
SELECT 
    p.bsn,
    p.pl_id,
    p.voornamen,
    p.geslachtsnaam,
    p.voorvoegsel,
    p.geboortedatum,
    p.geboorteplaats,
    p.geboorteland_code,
    p.geboorteland_omschrijving,
    p.geslachtsaanduiding,
    p.aNummer,
    v.verblijfplaats_straatnaam,
    v.verblijfplaats_huisnummer,
    v.verblijfplaats_huisnummertoevoeging,
    v.verblijfplaats_postcode,
    v.verblijfplaats_woonplaats,
    v.verblijfplaats_land_code,
    v.verblijfplaats_land_omschrijving,
    p.ax,
    p.hist
FROM probev.v_inw_ax_haal_centraal p
LEFT JOIN probev.v_vb_ax_haal_centraal v ON v.bsn = p.bsn AND v.ax = 'A' AND v.hist = 'A';

-- ============================================================================
-- Indexen voor performance (optioneel, maar aanbevolen)
-- ============================================================================

-- Index op BSN in inw_ax (als deze nog niet bestaat)
-- CREATE INDEX IF NOT EXISTS idx_inw_ax_bsn ON probev.inw_ax(bsn) WHERE ax = 'A' AND hist = 'A';

-- Index op BSN in vb_ax (als deze nog niet bestaat)
-- CREATE INDEX IF NOT EXISTS idx_vb_ax_bsn ON probev.vb_ax(bsn) WHERE ax = 'A' AND hist = 'A';

-- ============================================================================
-- Test queries
-- ============================================================================

-- Test: Haal eerste 10 personen op
-- SELECT * FROM probev.v_inw_ax_haal_centraal LIMIT 10;

-- Test: Haal persoon op BSN
-- SELECT * FROM probev.v_inw_ax_haal_centraal WHERE bsn = '168149291';

-- Test: Haal complete persoongegevens (met adres)
-- SELECT * FROM probev.v_personen_compleet_haal_centraal WHERE bsn = '168149291';

