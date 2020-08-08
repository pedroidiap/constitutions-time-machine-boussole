<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">


<xsl:template match="/">
	<html>
	<style>
		.alinea {
			vertical-align: super;
			font-size: 50%;
			margin-left: 1em;
			float:left;
		}
		.articleText {
			margin-left: 2em; 
		} 
		.ingress {
			font-style: oblique;
		}
		.nobullet {
			list-style: none;
		}
	</style>
    <body>
    	<h1>
			<xsl:value-of select="/norm/normMetadata/normNumber/number"/>
			<xsl:text> </xsl:text>
			<xsl:value-of select="/norm/normMetadata/shortTitles/title[@lang='fr']"/>
		</h1>
		<xsl:apply-templates select="//ingressText[@lang='fr']"/>
    	<xsl:apply-templates select="//article"/>
    </body>
	</html>
</xsl:template>

<xsl:template match="//ingressText[@lang='fr']">
	<div class="ingress">
		<p>
			<xsl:value-of select="authorityDescription"/>
		</p>
		<ul>
			<xsl:for-each select="basis">
				<li>
					<xsl:value-of select="."/>
				</li>
			</xsl:for-each>
		</ul>
		<p>
			<xsl:value-of select="formal"/>
		</p>
	</div>
</xsl:template>

<xsl:template match="//article">
	<div>
		<xsl:attribute name="id">
    		<xsl:text>a</xsl:text><xsl:value-of select="articleMetadata/articleNumber/number" />
    	</xsl:attribute>
		<h3>
			<xsl:value-of select="articleMetadata/articleForm"/>
			<xsl:text> </xsl:text>
			<xsl:value-of select="articleMetadata/articleNumber/number"/>
		</h3>
		<xsl:for-each select="articleBody/articleText">
		<p>
			<div class="alinea"><xsl:value-of select="partMetadata/partNumber"/></div>
			<xsl:for-each select="partTexts/partText/mixedText[@lang='fr']">
				<div class="articleText"><xsl:value-of select="."/></div>
			</xsl:for-each>
			<ul class="nobullet">
				<xsl:for-each select="subparts/articleText">
					<li>
						<xsl:value-of select="partMetadata/partNumber"/>
						<xsl:text> </xsl:text>
						<xsl:value-of select="partTexts/partText/mixedText[@lang='fr']"/>
					</li>
				</xsl:for-each>
			</ul>
		</p>
		</xsl:for-each>
	</div>
</xsl:template>

</xsl:stylesheet>