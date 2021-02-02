CHANGELOG for 2.0.x
===================

2.0.1 (2019-10-09)
------------------

 - [2082: Fix wrong initialisation of proxies with public properties](https://github.com/doctrine/mongodb-odm/pull/2082) thanks to @alcaeus and @Frederick888

2.0.0 (2019-10-02)
------------------

 - [2074: Remove references to legacy documentation](https://github.com/doctrine/mongodb-odm/pull/2074) thanks to @alcaeus and @malarzm
 - [2073: Throw hydrator exceptions when encountering invalid types](https://github.com/doctrine/mongodb-odm/pull/2073) thanks to @alcaeus
 - BC BREAK: [2069: Class name resolver performance optimisation](https://github.com/doctrine/mongodb-odm/pull/2069) thanks to @alcaeus

2.0.0-RC2 (2019-09-24)
----------------------

 - [2059: Introduce interface for command loggers](https://github.com/doctrine/mongodb-odm/pull/2059) thanks to @alcaeus
 - [2042: Update to PHPUnit 8](https://github.com/doctrine/mongodb-odm/pull/2042) thanks to @carusogabriel
 - [2014: Update to doctrine/cs v6](https://github.com/doctrine/mongodb-odm/pull/2014) thanks to @carusogabriel
 - [2041: &#91;2.0&#93; Fix wrong syntax for replaceRoot stage](https://github.com/doctrine/mongodb-odm/pull/2041) thanks to @alcaeus

2.0.0-RC1 (2019-06-05)
----------------------

 - [2025: Fix wrong usage of discriminator map in complex document inheritance chains](https://github.com/doctrine/mongodb-odm/pull/2025) thanks to @alcaeus and @josefsabl
 - [2006: Run appropriate query operations when replacing documents](https://github.com/doctrine/mongodb-odm/pull/2006) thanks to @alcaeus and @jmikola
 - [2002: Document class is unlisted in the discriminator map - using no discriminator map](https://github.com/doctrine/mongodb-odm/issues/2002) thanks to @josefsabl
 - [1996: Fix dir permissions for proxies](https://github.com/doctrine/mongodb-odm/pull/1996) thanks to @josefsabl
 - [1995: Proxy directory is created with wrong permissions](https://github.com/doctrine/mongodb-odm/issues/1995) thanks to @josefsabl
 - [1991: Fix generation of proxy classes](https://github.com/doctrine/mongodb-odm/pull/1991) thanks to @alcaeus and @strobox
 - [1989: Fix metadata storage when uploading GridFS files](https://github.com/doctrine/mongodb-odm/pull/1989) thanks to @alcaeus and @josefsabl
 - [1983: Allow returning null from getAssociationTargetClass](https://github.com/doctrine/mongodb-odm/pull/1983) thanks to @malarzm and @Dragonqos
 - [1967: Add missing `addFields` method for Stage abstract](https://github.com/doctrine/mongodb-odm/pull/1967) thanks to @Steveb-p
 - [1965: Fix wrong behavior of sortMeta ](https://github.com/doctrine/mongodb-odm/pull/1965) thanks to @Mihail0v
 - [1944: fix: replace string assertion with array assertion for $filter](https://github.com/doctrine/mongodb-odm/pull/1944) thanks to @thiver
 - [1932: &#91;2.0&#93; Fix serialisation of uninitialised PersistentCollection instances](https://github.com/doctrine/mongodb-odm/pull/1932) thanks to @alcaeus
 - [1926: Fix wrong capitalisation of attributes for GridFS mappings](https://github.com/doctrine/mongodb-odm/pull/1926) thanks to @alcaeus
 - [1922: Fix wrong usage of sequence vs. choice in XSD](https://github.com/doctrine/mongodb-odm/pull/1922) thanks to @alcaeus
 - [1918: Fix incorrect schema commands behaviour](https://github.com/doctrine/mongodb-odm/pull/1918) thanks to @Rybbow
 - [2013: Update default reference primier's code](https://github.com/doctrine/mongodb-odm/pull/2013) thanks to @malarzm
 - [1953: &#91;2.0&#93; Add missing documentation of same-namespace resolution drop](https://github.com/doctrine/mongodb-odm/pull/1953) thanks to @alcaeus
 - [1928: Use same documentation for notSaved in all annotations](https://github.com/doctrine/mongodb-odm/pull/1928) thanks to @alcaeus
 - [1920: Generating proxy classes only when file not exists](https://github.com/doctrine/mongodb-odm/issues/1920) thanks to @Rybbow
 - [1984: Mark classes as final](https://github.com/doctrine/mongodb-odm/pull/1984) thanks to @malarzm
 - [1960: Mark methods and classes as internal](https://github.com/doctrine/mongodb-odm/pull/1960) thanks to @malarzm and @alcaeus
 - [1956: Remove previously deprecated operations from Query Builder](https://github.com/doctrine/mongodb-odm/pull/1956) thanks to @jmikola
 - [1942: Finish renaming dirtyCheck methods](https://github.com/doctrine/mongodb-odm/pull/1942) thanks to @alcaeus
 - [1938: Renamed scheduledForDirtyCheck](https://github.com/doctrine/mongodb-odm/pull/1938) thanks to @juliusstoerrle
 - [1937: Removed PHP 5.5 workaround](https://github.com/doctrine/mongodb-odm/pull/1937) thanks to @juliusstoerrle
 - [2021: Dump xdebug filter to improve code coverage build performance](https://github.com/doctrine/mongodb-odm/pull/2021) thanks to @alcaeus
 - [2020: Make addStage Aggregation Builder method public](https://github.com/doctrine/mongodb-odm/pull/2020) thanks to @alcaeus
 - [2017: Allow specifying generic options for Query find operations](https://github.com/doctrine/mongodb-odm/pull/2017) thanks to @jmikola
 - [1969: remove unnecessary files for production](https://github.com/doctrine/mongodb-odm/pull/1969) thanks to @rvitaliy
 - [1963: Test against supported versions of MongoDB](https://github.com/doctrine/mongodb-odm/pull/1963) thanks to @alcaeus
 - [1958: Allow Symfony 3.4](https://github.com/doctrine/mongodb-odm/pull/1958) thanks to @Seb33300
 - [1693: Schema manager should handle shard key on reference](https://github.com/doctrine/mongodb-odm/pull/1693) thanks to @notrix

2.0.0-Beta1 (2018-12-24)
------------------------

Deprecated functionality has been removed. Please check the
[UPGRADE-2.0 document](https://github.com/doctrine/mongodb-odm/blob/2.0.0/UPGRADE-2.0.md)
to review the changes.

 - [1904: Always use dump method from VarDumper component](https://github.com/doctrine/mongodb-odm/pull/1904) thanks to @alcaeus
 - [1903: Throw exception on duplicate database names within a document](https://github.com/doctrine/mongodb-odm/pull/1903) thanks to @alcaeus and @vmattila
 - [1894: Fix inheritance of GridFS mapping properties](https://github.com/doctrine/mongodb-odm/pull/1894) thanks to @alcaeus
 - [1893: Fix missing proxy directory](https://github.com/doctrine/mongodb-odm/pull/1893) thanks to @alcaeus and @olvlvl
 - [1871: Enforce typemap](https://github.com/doctrine/mongodb-odm/pull/1871) thanks to @alcaeus
 - [1831: &#91;2.0&#93; Fix wrong element deletion in popFirst and popLast](https://github.com/doctrine/mongodb-odm/pull/1831) thanks to @alcaeus and @juliusxyg
 - [1829: Improve SchemaManager logic for comparing text indexes](https://github.com/doctrine/mongodb-odm/pull/1829) thanks to @jmikola
 - [1798: &#91;2.0&#93; Fix querying fields in reference structures](https://github.com/doctrine/mongodb-odm/pull/1798) thanks to @alcaeus and @malarzm
 - [1797: &#91;2.0&#93; Implicitly cascade remove operations when orphanRemoval is enabled](https://github.com/doctrine/mongodb-odm/pull/1797) thanks to @alcaeus
 - [1786: &#91;2.0&#93; Fix hydration of proxy objects with lazy public properties](https://github.com/doctrine/mongodb-odm/pull/1786) thanks to @alcaeus
 - [1906: Forbid mapping class by more than one AbstractDocument](https://github.com/doctrine/mongodb-odm/pull/1906) thanks to @malarzm
 - [1905: Don't dump to stdout in query command](https://github.com/doctrine/mongodb-odm/pull/1905) thanks to @alcaeus
 - [1902: Remove eager cursor functionality without replacement](https://github.com/doctrine/mongodb-odm/pull/1902) thanks to @alcaeus
 - [1901: Fix skipped tests](https://github.com/doctrine/mongodb-odm/pull/1901) thanks to @alcaeus
 - [1896: Drop dependency on doctrine/common](https://github.com/doctrine/mongodb-odm/pull/1896) thanks to @alcaeus
 - [1895: Drop &quot;simple&quot; attribute from references in XML schema](https://github.com/doctrine/mongodb-odm/pull/1895) thanks to @alcaeus
 - [1892: Use dedicated assertContainsOnlyInstancesOf assertion](https://github.com/doctrine/mongodb-odm/pull/1892) thanks to @carusogabriel
 - [1887: Exception when persisting class unlisted in disciminator map (Issue 867)](https://github.com/doctrine/mongodb-odm/pull/1887) thanks to @watari
 - [1886: add php 7.3 to travis](https://github.com/doctrine/mongodb-odm/pull/1886) thanks to @andreybolonin
 - [1880: Optimized nested collections deletion in DocumentPersister](https://github.com/doctrine/mongodb-odm/pull/1880) thanks to @watari
 - [1878: Update CHANGELOG-2.0.md](https://github.com/doctrine/mongodb-odm/pull/1878) thanks to @ajant
 - [1872: Sort packages in composer.json](https://github.com/doctrine/mongodb-odm/pull/1872) thanks to @garak
 - [1867: Update PHPStan](https://github.com/doctrine/mongodb-odm/pull/1867) thanks to @alcaeus
 - [1860: Update to Doctrine CS 5.0](https://github.com/doctrine/mongodb-odm/pull/1860) thanks to @alcaeus
 - [1847: &#91;2.0&#93; Test update: ODM no longer supports PHP &lt; 7.2](https://github.com/doctrine/mongodb-odm/pull/1847) thanks to @caciobanu and @alcaeus
 - [1845: &#91;2.0&#93; Upgrade dependencies version](https://github.com/doctrine/mongodb-odm/pull/1845) thanks to @caciobanu
 - [1844: &#91;2.0&#93; Require php : ^7.2](https://github.com/doctrine/mongodb-odm/pull/1844) thanks to @caciobanu and @alcaeus
 - [1836: Added dev autoload for composer &amp; removed it from tests boostrap.](https://github.com/doctrine/mongodb-odm/pull/1836) thanks to @caciobanu
 - [1834: &#91;2.0&#93; Consistently use kebab-case in XML mappings](https://github.com/doctrine/mongodb-odm/pull/1834) thanks to @alcaeus
 - [1827: Remove obsolete syntaxCheck option in PHPUnit config](https://github.com/doctrine/mongodb-odm/pull/1827) thanks to @jmikola
 - [1825: Use PSR-4](https://github.com/doctrine/mongodb-odm/pull/1825) thanks to @caciobanu
 - [1820: Use dedicated PHPUnit assertions](https://github.com/doctrine/mongodb-odm/pull/1820) thanks to @carusogabriel
 - [1819: Improvements](https://github.com/doctrine/mongodb-odm/pull/1819) thanks to @carusogabriel
 - [1812:  &#91;2.0&#93; Drop namespace property from ClassMetadata](https://github.com/doctrine/mongodb-odm/pull/1812) thanks to @caciobanu and @alcaeus
 - [1803: Increase PHPStan's level](https://github.com/doctrine/mongodb-odm/pull/1803) thanks to @malarzm
 - [1802: &#91;2.0&#93; Drop bool from supported values - Configuration class](https://github.com/doctrine/mongodb-odm/pull/1802) thanks to @caciobanu
 - [1801: Stop accepting bools in int|bool Configuration methods](https://github.com/doctrine/mongodb-odm/issues/1801) thanks to @malarzm
 - [1800: Fix new CS violations](https://github.com/doctrine/mongodb-odm/pull/1800) thanks to @malarzm
 - [1799: &#91;2.0&#93; Clean up docblocks in Configuration class - #1796](https://github.com/doctrine/mongodb-odm/pull/1799) thanks to @caciobanu and @alcaeus
 - [1771: &#91;2.0&#93; Forbid combining repositoryMethod with skip, sort and limit](https://github.com/doctrine/mongodb-odm/pull/1771) thanks to @malarzm and @alcaeus
 - [1770: Use ::class where possible](https://github.com/doctrine/mongodb-odm/pull/1770) thanks to @malarzm
 - [1762: Stop ignoring DoubleQuoteUsage.ContainsVar](https://github.com/doctrine/mongodb-odm/pull/1762) thanks to @malarzm
 - [1759: Stop ignoring TypeHintDeclaration.MissingPropertyTypeHint](https://github.com/doctrine/mongodb-odm/pull/1759) thanks to @malarzm
 - [1758: Various small CS fixes](https://github.com/doctrine/mongodb-odm/pull/1758) thanks to @malarzm
 - [1757: Stop ignoring ControlStructures.EarlyExit](https://github.com/doctrine/mongodb-odm/pull/1757) thanks to @malarzm
 - [1756: Stop ignoring UnusedPrivateElements sniffs](https://github.com/doctrine/mongodb-odm/pull/1756) thanks to @malarzm
 - [1755: Remove NativePhpunitTask](https://github.com/doctrine/mongodb-odm/pull/1755) thanks to @malarzm
 - [1743: Add phpcs to build and apply automatic fixes](https://github.com/doctrine/mongodb-odm/pull/1743) thanks to @alcaeus
 - [1734: &#91;2.0&#93; Remove YAML mapping support](https://github.com/doctrine/mongodb-odm/pull/1734) thanks to @malarzm and @alcaeus
 - [1733: &#91;2.0&#93; Merge ClassMetadataInfo into ClassMetadata](https://github.com/doctrine/mongodb-odm/pull/1733) thanks to @carusogabriel and @alcaeus
 - [1722: &#91;2.0&#93; Disallow nested commits](https://github.com/doctrine/mongodb-odm/pull/1722) thanks to @malarzm
 - [1721: &#91;2.0&#93; Remove DocumentManager::createDbRef](https://github.com/doctrine/mongodb-odm/pull/1721) thanks to @malarzm
 - [1720: &#91;2.0&#93; Remove deprecations in query helpers](https://github.com/doctrine/mongodb-odm/pull/1720) thanks to @malarzm
 - [1719: &#91;2.0&#93; Remove repositories' magic findBy and findOneBy](https://github.com/doctrine/mongodb-odm/pull/1719) thanks to @malarzm
 - [1718: &#91;2.0&#93; Remove database creation from SchemaManager](https://github.com/doctrine/mongodb-odm/pull/1718) thanks to @malarzm
 - [1717: &#91;2.0&#93; Make DefaultRepositoryFactory final](https://github.com/doctrine/mongodb-odm/pull/1717) thanks to @malarzm
 - [1716: &#91;2.0&#93; Remove slaveOkay remainders](https://github.com/doctrine/mongodb-odm/pull/1716) thanks to @malarzm
 - [1715: &#91;2.0&#93; Drop support for UnitOfWork::flush($document)](https://github.com/doctrine/mongodb-odm/pull/1715) thanks to @malarzm and @alcaeus
 - [1714: Add PHPStan](https://github.com/doctrine/mongodb-odm/pull/1714) thanks to @carusogabriel and @alcaeus
 - [1708: &#91;2.0&#93; Drop commands to generate repository and entity stubs](https://github.com/doctrine/mongodb-odm/pull/1708) thanks to @alcaeus
 - [1692: Use Null Coalesce Operator](https://github.com/doctrine/mongodb-odm/pull/1692) thanks to @carusogabriel
 - [1691: Clean elses](https://github.com/doctrine/mongodb-odm/pull/1691) thanks to @carusogabriel
 - [1673: &#91;2.0&#93; Remove slaveOkay](https://github.com/doctrine/mongodb-odm/pull/1673) thanks to @malarzm
 - [1502: &#91;2.0&#93; Use storeAs=dbRef as the new default setting for references](https://github.com/doctrine/mongodb-odm/pull/1502) thanks to @coudenysj
 - [1485: &#91;2.0&#93; Bump PHP to 7.0](https://github.com/doctrine/mongodb-odm/pull/1485) thanks to @malarzm
 - [1480: &#91;2.0&#93; Remove simple references leftovers](https://github.com/doctrine/mongodb-odm/pull/1480) thanks to @malarzm
 - [1478: &#91;2.0&#93; Remove DiscriminatorField's name and fieldName](https://github.com/doctrine/mongodb-odm/pull/1478) thanks to @malarzm
 - [1476: &#91;2.0&#93; Remove requireIndexes and stuff thereto related](https://github.com/doctrine/mongodb-odm/pull/1476) thanks to @malarzm
 - [1475: &#91;2.0&#93; Remove deprecated argument from DocumentPersister::refresh](https://github.com/doctrine/mongodb-odm/pull/1475) thanks to @malarzm
 - [1474: &#91;2.0&#93; Remove deprecated increment type](https://github.com/doctrine/mongodb-odm/pull/1474) thanks to @malarzm
 - [1471: &#91;2.0&#93; Remove deprecated annotations](https://github.com/doctrine/mongodb-odm/pull/1471) thanks to @malarzm
 - [867: Exception when persisting class unlisted in disciminator map](https://github.com/doctrine/mongodb-odm/issues/867) thanks to @jmikola
 - [563: If the database value of @DiscriminatorField changes to unsupported value, an Exception should be thrown instead of PHP Notice](https://github.com/doctrine/mongodb-odm/issues/563) thanks to @vmattila
 - [1891: Fix fieldName with field-name](https://github.com/doctrine/mongodb-odm/pull/1891) thanks to @olvlvl
 - [1883: Added more strict types for docs (Issue #1783)](https://github.com/doctrine/mongodb-odm/pull/1883) thanks to @watari
 - [1881: &#91;2.0&#93; Documentation: fix schema create command namespace](https://github.com/doctrine/mongodb-odm/pull/1881) thanks to @l-vo
 - [1856: Add UPGRADE document for ODM 2.0](https://github.com/doctrine/mongodb-odm/pull/1856) thanks to @alcaeus
 - [1839: Fix code blocks](https://github.com/doctrine/mongodb-odm/pull/1839) thanks to @jdreesen
 - [1783: &#91;2.0&#93; Update docs for strict typing](https://github.com/doctrine/mongodb-odm/issues/1783) thanks to @alcaeus
 - [1910: Separate index creation options from index options](https://github.com/doctrine/mongodb-odm/pull/1910) thanks to @alcaeus and @jmikola
 - [1875: Drop doctrine/common proxies in favor of ProxyManager](https://github.com/doctrine/mongodb-odm/pull/1875) thanks to @alcaeus
 - [1866: Replace QueryLogger in tests with CommandLogger](https://github.com/doctrine/mongodb-odm/pull/1866) thanks to @alcaeus
 - [1848: Finalize sharding support for 2.0](https://github.com/doctrine/mongodb-odm/pull/1848) thanks to @alcaeus
 - [1846: &#91;2.0&#93; Add type hints](https://github.com/doctrine/mongodb-odm/pull/1846) thanks to @caciobanu and @alcaeus
 - [1814: &#91;2.0&#93; Validate mapping files against schema](https://github.com/doctrine/mongodb-odm/pull/1814) thanks to @alcaeus
 - [1807: &#91;2.0&#93; Separate ID mapping from fields in XML driver](https://github.com/doctrine/mongodb-odm/pull/1807) thanks to @alcaeus
 - [1790: &#91;2.0&#93; Add GridFS implementation on top of mongodb/mongodb](https://github.com/doctrine/mongodb-odm/pull/1790) thanks to @alcaeus
 - [1553: &#91;2.0&#93; Replace doctrine/mongodb for mongodb/mongodb and ext-mongodb](https://github.com/doctrine/mongodb-odm/pull/1553) thanks to @alcaeus
 - [1051: &#91;RFC&#93; &quot;Nested&quot; calls to DocumentManager::flush()](https://github.com/doctrine/mongodb-odm/issues/1051) thanks to @alcaeus
