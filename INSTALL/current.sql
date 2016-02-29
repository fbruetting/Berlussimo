SET @avalue = '1';
SELECT SUM(acount)
FROM (
  SELECT COUNT(*) AS acount
  FROM HAUS
  WHERE HAUS_AKTUELL = @avalue
  UNION
  SELECT COUNT(*) AS acount
  FROM BAUSTELLEN_EXT
  WHERE AKTUELL = @avalue
  UNION
  SELECT COUNT(*) AS acount
  FROM BENUTZER_MODULE
  WHERE AKTUELL = @avalue
  UNION
  SELECT COUNT(*) AS acount
  FROM BENUTZER_PARTNER
  WHERE AKTUELL = @avalue
  UNION
  SELECT COUNT(*) AS acount
  FROM BERICHTE_USER
  WHERE AKTUELL = @avalue
  UNION
  SELECT COUNT(*) AS acount
  FROM BK_ABRECHNUNGEN
  WHERE AKTUELL = @avalue
  UNION
  SELECT COUNT(*) AS acount
  FROM BK_ABRECHNUNGEN_KONTEN
  WHERE AKTUELL = @avalue
  UNION
  SELECT COUNT(*) AS acount
  FROM BK_ANPASSUNG
  WHERE AKTUELL = @avalue
  UNION
  SELECT COUNT(*) AS acount
  FROM BENUTZER_MODULE
  WHERE AKTUELL = @avalue
) AS notCurrent;