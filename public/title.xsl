<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">


<xsl:template match="/">
			<xsl:value-of select="/norm/normMetadata/normNumber/number"/>
			<xsl:text> </xsl:text>
			<xsl:value-of select="/norm/normMetadata/shortTitles/title[@lang='fr']"/>
</xsl:template>

</xsl:stylesheet>