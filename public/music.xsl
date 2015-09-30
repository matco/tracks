<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet
	version="1.0"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output
	method="html"
	encoding="utf-8"
	version="1.0"
	omit-xml-declaration="yes"
	doctype-public="-//W3C//DTD XHTML 1.1//EN"
	doctype-system="http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"
	indent="yes"
	media-type="application/xhtml+xml" />

	<!--parameters-->
	<xsl:param name="field">name</xsl:param>
	<xsl:param name="direction">asc</xsl:param>

	<xsl:template match="files">
		<table style="width: 100%; max-height: 160px; background: none; border: 1px dotted #8EB0DB; padding: 0; margin: 0 0 1em 0;">
			<thead style="padding: 0; margin: 0; border: 0;">
				<tr style="height: 20px; padding: 0; margin: 0; border: 0; background-color: white;">
					<th style="vertical-align: middle; padding: 0; margin: 0; border: 0; height: 25px;">
						<a id="name" href="name" class="sortable">Name
							<img>
								<xsl:attribute name="src">
									<xsl:if test="$direction = 'asc'">
										<xsl:value-of select="'public/images/fold.png'"/>
									</xsl:if>
									<xsl:if test="$direction = 'desc'">
										<xsl:value-of select="'public/images/unfold.png'"/>
									</xsl:if>
								</xsl:attribute>
								<xsl:attribute name="style">
									<xsl:if test="$field = 'name'">
										<xsl:value-of select="'margin-bottom: 3px; opacity: 1;'"/>
									</xsl:if>
									<xsl:if test="$field != 'name'">
										<xsl:value-of select="'margin-bottom: 3px; opacity: 0.1;'"/>
									</xsl:if>
								</xsl:attribute>
							</img>
						</a>
					</th>
					<th style="width: 160px; vertical-align: middle; padding: 0; margin: 0; border: 0;">
						<a id="playcount" href="playcount" class="sortable">Play count
							<img>
								<xsl:attribute name="src">
									<xsl:if test="$direction = 'asc'">
										<xsl:value-of select="'public/images/fold.png'"/>
									</xsl:if>
									<xsl:if test="$direction = 'desc'">
										<xsl:value-of select="'public/images/unfold.png'"/>
									</xsl:if>
								</xsl:attribute>
								<xsl:attribute name="style">
									<xsl:if test="$field = 'playcount'">
										<xsl:value-of select="'margin-bottom: 3px; opacity: 1;'"/>
									</xsl:if>
									<xsl:if test="$field != 'playcount'">
										<xsl:value-of select="'margin-bottom: 3px; opacity: 0.1;'"/>
									</xsl:if>
								</xsl:attribute>
							</img>
						</a>
					</th>
					<th style="width: 90px; vertical-align: middle; padding: 0; margin: 0; border: 0;">
						<a id="note" href="note" class="sortable">Note
							<img>
								<xsl:attribute name="src">
									<xsl:if test="$direction = 'asc'">
										<xsl:value-of select="'public/images/fold.png'"/>
									</xsl:if>
									<xsl:if test="$direction = 'desc'">
										<xsl:value-of select="'public/images/unfold.png'"/>
									</xsl:if>
								</xsl:attribute>
								<xsl:attribute name="style">
									<xsl:if test="$field = 'note'">
										<xsl:value-of select="'margin-bottom: 3px; opacity: 1;'"/>
									</xsl:if>
									<xsl:if test="$field != 'note'">
										<xsl:value-of select="'margin-bottom: 3px; opacity: 0.1;'"/>
									</xsl:if>
								</xsl:attribute>
							</img>
						</a>
					</th>
				</tr>
			</thead>
			<tbody>
				<xsl:for-each select="file">
					<!--<xsl:sort select="file/@{$field}" order="{$direction}" />-->
					<!--<xsl:apply-templates select="." />-->
					<tr>
						<xsl:attribute name="style">
							<xsl:choose>
								<xsl:when test="position() mod 2 = 0">
									height: 15px; background-color: #D5DFEE;
								</xsl:when>
								<xsl:otherwise>
									height: 15px;
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
						<td><a href="{id}" class="{round(note)}"><xsl:value-of select="name" /></a></td>
						<td><xsl:value-of select="playcount" /></td>
						<td>
							<xsl:if test="note = 'NA'">
								NA
							</xsl:if>
							<xsl:if test="note != 'NA'">
								<xsl:call-template name="loop">
									<xsl:with-param name="start" select="0" />
									<xsl:with-param name="stop" select="round(note)" />
									<xsl:with-param name="color" select="'yellow'" />
								</xsl:call-template>
								<xsl:call-template name="loop">
									<xsl:with-param name="start" select="round(note)" />
									<xsl:with-param name="stop" select="5" />
									<xsl:with-param name="color" select="'white'" />
								</xsl:call-template>
							</xsl:if>
						</td>
					</tr>
				</xsl:for-each>
			</tbody>
		</table>
		<div id="control">
			<xsl:value-of select="@min" /> - <xsl:value-of select="@max" /> (<xsl:value-of select="@number" />)
			 -
			<a id="previous" style="opacity: 0.2">Previous</a>
			 -
			<a id="next" style="opacity: 0.2">Next</a>
		</div>
	</xsl:template>

	<xsl:template match="file">
	</xsl:template>

	<xsl:template name="loop">
		<xsl:param name="start" select="0" />
		<xsl:param name="stop" select="0" />
		<xsl:param name="color" select="white" />

		<xsl:if test="$start &lt; $stop">
			<xsl:if test="$color = 'white'">
				<img src="public/icons/star_white.png" />
			</xsl:if>

			<xsl:if test="$color = 'yellow'">
				<img src="public/icons/star.png" />
			</xsl:if>

			<xsl:call-template name="loop">
				<xsl:with-param name="start" select="($start) + 1" />
				<xsl:with-param name="stop" select="$stop" />
				<xsl:with-param name="color" select="$color" />
			</xsl:call-template>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>