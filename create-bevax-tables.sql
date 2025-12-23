-- Maak bevax tabellen aan zoals ze waren
CREATE SCHEMA IF NOT EXISTS bevax;

-- Personen tabel
CREATE TABLE IF NOT EXISTS bevax."Personen" (
    id INTEGER PRIMARY KEY,
    bsn TEXT NOT NULL,
    voornamen TEXT NOT NULL,
    voorvoegsel TEXT,
    geslachtsnaam TEXT NOT NULL,
    geboortedatum TEXT NOT NULL,
    geslacht TEXT,
    "burgerlijkeStaat" TEXT,
    verblijfstitel TEXT,
    ouder1Id INTEGER,
    ouder2Id INTEGER,
    isIngezetene BOOLEAN,
    "createdAt" TIMESTAMP,
    "updatedAt" TIMESTAMP,
    anr TEXT
);

-- Zaken tabel
CREATE TABLE IF NOT EXISTS bevax."Zaken" (
    id INTEGER PRIMARY KEY,
    "zaakType" TEXT,
    "bronOrganisatie" TEXT,
    "startDatum" DATE,
    "eindDatum" DATE,
    status TEXT,
    omschrijving TEXT,
    "betrokkeneId" INTEGER,
    "behandelaarId" INTEGER,
    context JSONB,
    historie JSONB,
    "updatedAt" TIMESTAMP,
    "createdAt" TIMESTAMP
);

-- Adressen tabel
CREATE TABLE IF NOT EXISTS bevax."Adressen" (
    id INTEGER PRIMARY KEY,
    "persoonId" INTEGER,
    straat TEXT,
    huisnummer TEXT,
    huisnummertoevoeging TEXT,
    postcode TEXT,
    plaats TEXT,
    land TEXT,
    "soortAdres" TEXT,
    "begindatum" DATE,
    "einddatum" DATE,
    "createdAt" TIMESTAMP,
    "updatedAt" TIMESTAMP
);

-- Andere tabellen
CREATE TABLE IF NOT EXISTS bevax."Erkenningen" (
    id INTEGER PRIMARY KEY,
    "persoonId" INTEGER,
    "erkennerId" INTEGER,
    datum DATE,
    "createdAt" TIMESTAMP,
    "updatedAt" TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bevax."Gezagsverhoudingen" (
    id INTEGER PRIMARY KEY,
    "persoonId" INTEGER,
    "ouderId" INTEGER,
    soort TEXT,
    "begindatum" DATE,
    "einddatum" DATE,
    "createdAt" TIMESTAMP,
    "updatedAt" TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bevax."Huwelijken" (
    id INTEGER PRIMARY KEY,
    "persoon1Id" INTEGER,
    "persoon2Id" INTEGER,
    "huwelijksdatum" DATE,
    "ontbindingsdatum" DATE,
    "createdAt" TIMESTAMP,
    "updatedAt" TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bevax."Nationaliteiten" (
    id INTEGER PRIMARY KEY,
    "persoonId" INTEGER,
    nationaliteit TEXT,
    "begindatum" DATE,
    "einddatum" DATE,
    "createdAt" TIMESTAMP,
    "updatedAt" TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bevax."Reisdocumenten" (
    id INTEGER PRIMARY KEY,
    "persoonId" INTEGER,
    "documentnummer" TEXT,
    "soortDocument" TEXT,
    "uitgiftedatum" DATE,
    "vervaldatum" DATE,
    "uitgevendeInstantie" TEXT,
    "createdAt" TIMESTAMP,
    "updatedAt" TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bevax."Mutaties" (
    id INTEGER PRIMARY KEY,
    "persoonId" INTEGER,
    soort TEXT,
    "oudeWaarde" TEXT,
    "nieuweWaarde" TEXT,
    datum TIMESTAMP,
    "createdAt" TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bevax."PersoonFavoriet" (
    id INTEGER PRIMARY KEY,
    "persoonId" INTEGER,
    "gebruikerId" INTEGER,
    "createdAt" TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bevax."ZaakFavoriet" (
    id INTEGER PRIMARY KEY,
    "zaakId" INTEGER,
    "gebruikerId" INTEGER,
    "createdAt" TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bevax."BrpApiLogs" (
    id INTEGER PRIMARY KEY,
    "endpoint" TEXT,
    "requestData" JSONB,
    "responseData" JSONB,
    "statusCode" INTEGER,
    "createdAt" TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bevax."Config" (
    id INTEGER PRIMARY KEY,
    "key" TEXT UNIQUE,
    "value" TEXT,
    "updatedAt" TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bevax."RniPersonen" (
    id INTEGER PRIMARY KEY,
    bsn TEXT,
    voornamen TEXT,
    voorvoegsel TEXT,
    geslachtsnaam TEXT,
    geboortedatum TEXT,
    "createdAt" TIMESTAMP,
    "updatedAt" TIMESTAMP
);








