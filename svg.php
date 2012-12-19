<?php

$aSvgHeader = array(
		'<?xml version="1.0" encoding="iso-8859-1"?>',
		'<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20000303 Stylable//EN" "http://www.w3.org/TR/2000/03/WD-SVG-20000303/DTD/svg-20000303-stylable.dtd">',
		'<svg',
		'xmlns:svg="http://www.w3.org/2000/svg"',
		'xmlns="http://www.w3.org/2000/svg"',
		'version="1.0"',
		'x="0"',
		'y="0"'
);


function StartSVG(&$aSvgSurface, $iHeight, $iWidth)
{
		global $aSvgHeader;
		$t = 0;
		for ($t = 0; $t < sizeof($aSvgHeader); $t++) {
				$aSvgSurface[$t] = $aSvgHeader[$t];
		}
		$aSvgSurface[sizeof($aSvgSurface)] = "width=\"$iWidth\"";
		$aSvgSurface[sizeof($aSvgSurface)] = "height=\"$iHeight\">";
}

function CloseSVG(&$aSvgSurface)
{
		$aSvgSurface[sizeof($aSvgSurface)] = '</svg>';
}

function ShowSVG($aSvgSurface, $sFilename)
{
		$fpSvgFile = fopen($sFilename, "w");
		
		for ($t = 0; $t < sizeof($aSvgSurface); $t++) {
				fputs($fpSvgFile, $aSvgSurface[$t] . "\n\r");
		}
		fclose($fpSvgFile);
}

function CircleArc(&$aSvgSurface, $iCenterx, $iCentery, $iRadius, $iStartwinkel, $iEndwinkel, $iColor)
{
		$iPos               = sizeof($aSvgSurface);
		$aSvgSurface[$iPos] = "<polygon fill=\"#$iColor\" stroke=\"black\" stroke-width=\"1px\" points=\"";
		$aSvgSurface[$iPos] = $aSvgSurface[$iPos] . "$iCenterx,$iCentery";
		
		for ($t = $iStartwinkel; $t <= $iEndwinkel; $t++) {
				$iX1                = round($iCenterx + (cos(deg2rad($t)) * $iRadius));
				$iY1                = round($iCentery - (sin(deg2rad($t)) * $iRadius));
				$aSvgSurface[$iPos] = $aSvgSurface[$iPos] . " $iX1,$iY1";
		}
		$aSvgSurface[$iPos] = $aSvgSurface[$iPos] . " $iCenterx,$iCentery";
		$aSvgSurface[$iPos] = $aSvgSurface[$iPos] . "\"/>";
}

function Rectangle(&$aSvgSurface, $iX1, $iY1, $iWidth, $iHeight, $iColor)
{
		$iPos               = sizeof($aSvgSurface);
		$aSvgSurface[$iPos] = "<rect fill=\"#$iColor\" stroke=\"#000000\" stroke-width=\"1\" x=\"$iX1\" y=\"$iY1\" width=\"$iWidth\" height=\"$iHeight\" />";
}

function DrawText(&$aSvgSurface, $iPosx, $iPosy, $sMessage)
{
		$iPos               = sizeof($aSvgSurface);
		$aSvgSurface[$iPos] = "<text style=\"font-size:12px\" x=\"$iPosx\" y=\"$iPosy\">" . $sMessage . "</text>";
}


function Tortendiagramm($aValues, $aText, $sName)
{
		$iRadiusTorte   = 50;
		$iPicsizeWidth  = 400;
		$iPicsizeHeight = 200;
		
		$aBild = array();
		StartSVG($aBild, $iPicsizeHeight, $iPicsizeWidth);
		
		// darkred
		$aStartcolor = array(
				184,
				0,
				0
		);
		// yellow
		$aEndcolor   = array(
				255,
				244,
				126
		);
		
		
		$aColor = array();
		$fDivR  = ($aEndcolor[0] - $aStartcolor[0]) / (sizeof($aValues) - 1);
		$fDivG  = ($aEndcolor[1] - $aStartcolor[1]) / (sizeof($aValues) - 1);
		$fDivB  = ($aEndcolor[2] - $aStartcolor[2]) / (sizeof($aValues) - 1);
		
		for ($t = 0; $t < sizeof($aValues); $t++) {
				$aColor[$t] = str_pad((dechex(floor(round($aStartcolor[0] + ($fDivR * $t))))), 2, "0", STR_PAD_LEFT) . str_pad((dechex(floor(round($aStartcolor[1] + ($fDivG * $t))))), 2, "0", STR_PAD_LEFT) . str_pad((dechex(floor(round($aStartcolor[2] + ($fDivB * $t))))), 2, "0", STR_PAD_LEFT);
		}
		
		$iSum = 0;
		for ($t = 0; $t < sizeof($aValues); $t++) {
				$iSum = $iSum + $aValues[$t];
		}
		
		$iStart = 0;
		for ($t = 0; $t < sizeof($aValues); $t++) {
				$iEnde = $iStart + round(($aValues[$t] / $iSum) * 360);
				CircleArc($aBild, $iRadiusTorte + 1, $iRadiusTorte + 1, $iRadiusTorte, $iStart, $iEnde, $aColor[$t]);
				$iStart = $iEnde;
		}
		
		for ($t = 0; $t < sizeof($aText); $t++) {
				Rectangle($aBild, 20 + ($iRadiusTorte * 2), ($t * 15) + 1, 20, 10, $aColor[$t]);
				
				DrawText($aBild, 50 + ($iRadiusTorte * 2), ($t * 15) + 11, $aText[$t] . ' (' . round($aValues[$t] / $iSum * 100) . ' %, ' . $aValues[$t] . ' absolut)');
		}
		
		CloseSVG($aBild);
		ShowSVG($aBild, $sName);
}
