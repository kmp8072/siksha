Description of SVGGraph import
==============================

1. Download latest version from http://www.goat1000.com/svggraph.php

2. Add commit with new version to Totara repository at https://github.com/totara/SVGGraph

3. Merge changes into the release branch

4. Copy a snapshot of release branch to this directory

5. Update version in totara/core/thirdpartylibs.xml

6. reapply and test RTL hack in SVGGraphLegend::Draw() from TL-6573

Petr Skoda
