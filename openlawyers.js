var bDebug=0;
var aHiddenColumns=new Array;

function destroy()
{
  parent.close();
}

function noframe()
{
	if (top.frames.length>0) { top.location.href="index.php"; }
}

// die Methode reset() löscht nur direkte Nutzereingaben; bei einer Vorausfüllung aufgrund vorangegangener Eingaben - POST erhalten - hilft die Methode nicht

function resetFORM(which)
{
        oFormElement=document.forms[which];
        for ($t=0;$t<oFormElement.length;$t++)
        {        
                // Buttoninhalte, hiddenfields, radio- und checkboxes besser nicht löschen ...
   
                if ((oFormElement.elements[$t].type!="submit") 
                        && (oFormElement.elements[$t].type!="hidden") 
                                && (oFormElement.elements[$t].type!="button")
                                        && (oFormElement.elements[$t].type!="radio")
                                                && (oFormElement.elements[$t].type!="checkbox"))
                                { oFormElement.elements[$t].value=""; }
        }
}

// aktiviere Buttons bei obligatorischer Auswahl; Sonderstatus "disabled" muss erstes Argument sein
// falls bestimmte Buttons z.B. bei abgelegten Akten nicht wieder aktiviert werden sollen ... 

function actButtons()
{
        if (actButtons.arguments.length>0 && String(actButtons.arguments[0])!="disabled")
        {
                for (t=0;t<actButtons.arguments.length;t++)
                {
                        if (String(actButtons.arguments[t])!='')
                        {
                                oElement=document.getElementsByName(String(actButtons.arguments[t]))[0];
                                if (typeof(oElement)!="undefined")
                                {
                                        oElement.disabled=false;
                                }
                        }
                }
        }
}

// Auswahl aus einer vorher generierten Tabelle, um <select>-Liste zu imitieren; überträgt
// angeclicktes Element in entsprechendes <form>-feld; return-wert wird bei Posteingang/-ausgang ausgewertet
// Zusätzlich aktiviere Buttons bei obligatorischer Auswahl; Sonderstatus "disabled" muss zweites Argument sein
// falls bestimmte Buttons z.B. bei abgelegten Akten nicht wieder aktiviert werden sollen ... 


var last; 
var lastButton=new Array();

function colorize(whichID)
{
	if (typeof(whichID)!="undefined" && whichID!="" && whichID!="b")
	{
                if (lastButton.length>0)
                {
                        for (i=0;i<lastButton.length;i++)
                        {
                                document.getElementsByName(lastButton[i])[0].disabled=true;
                        }
                        lastButton.splice(0,lastButton.length);
                }
                
                if (colorize.arguments.length>1 && String(colorize.arguments[1])!="disabled")
                {
                        
                        for (t=1;t<colorize.arguments.length;t++)
                        {
                                if (String(colorize.arguments[t])!='')
                                {
                                        oElement=document.getElementsByName(String(colorize.arguments[t]))[0];

                                        if (typeof(oElement)!="undefined")
                                        {
                                                oElement.disabled=false;
                                                lastButton[lastButton.length]=colorize.arguments[t];
                                        }
                                }
                        }
                }

		t=String(whichID);
		if (last!="null")
		{
			document.getElementById(last).style.backgroundColor="transparent";document.getElementById(last).style.color="000000";
		}
		last=t;
		document.getElementById(last).style.backgroundColor="DCDCDC";document.getElementById(last).style.color="000000";
		document.forms["which"].elements["zeile"].value=last;
	        return true;
	}
	return false;
}

function initlist()
{
	last="null";
}

// die ErrorBox muss wenigsten mit erzwungenem Leerzeichen gefüllt sein, sonst existiert kein KindElement Textobjekt

function proofChecked()
{
      if (document.forms['which'].elements['zeile'].value=='')
      {
        alert(document.getElementById('errormessage').childNodes[0]);
        document.getElementById('errormessage').lastChild.data="HA NICHTS !";
        document.getElementById('error').style.display='block';
        return false;
      } 
      document.which.submit();
}


// Sonstige

function ticker()
{
	var timer = setInterval("ShowTime()",1000);
}

function ShowTime()
{
	var acttime=new Date();

	parent.statusline.document.forms["dateobject"].elements["datetime"].value=acttime.toLocaleString();
}	

var lastmenu="null";

function menuinit()
{
	lastmenu="null";
}

function menuover(whichID)
{
	document.getElementById(whichID).style.backgroundColor="000000";
	document.getElementById(whichID).style.color="ffffff";
}

function menuout(whichID)
{
	if (lastmenu!=whichID)
    {	
		document.getElementById(whichID).style.backgroundColor="DCDCDC";
		document.getElementById(whichID).style.color="000000";
	 }
	
}

function menuclick(whichID)
{
	document.getElementById(whichID).style.backgroundColor="000000";
	document.getElementById(whichID).style.color="ffffff";
	if (lastmenu!="null")
	{
		document.getElementById(lastmenu).style.backgroundColor="DCDCDC";
		document.getElementById(lastmenu).style.color="000000";
	}	
	lastmenu=whichID;
}

function Debug_Message(sText)
{
        if (bDebug==0 && !confirm(sText)) { bDebug=1; }
}

/* function hinweis(sText)
{
        oBox=document.getElementById("error");
        oBox.

} */

function showColumn(oCallerObject,iIndex)
{
        var aTable=oCallerObject.parentNode.parentNode.parentNode;
        var iCellIndex=oCallerObject.parentNode.cellIndex;
        
        // Zellen der spalte tauchen wieder auf (außer Kopfzeile)
        
        for (t=1;t<aTable.rows.length;t++)
        {
                aTable.rows[t].cells[iCellIndex].style.overflow='auto';
                aTable.rows[t].cells[iCellIndex].style.maxWidth='';
                aTable.rows[t].cells[iCellIndex].style.color='000000';
                aTable.rows[t].cells[iCellIndex].style.whiteSpace='normal';
                
        }

        // Kopfzeile herstellen
        
        oCallerObject.parentNode.parentNode.replaceChild(aHiddenColumns[iIndex], oCallerObject.parentNode);
}

function hideColumn(oCallerObject)
{
        var aTable=oCallerObject.parentNode.parentNode.parentNode;
        var iCellIndex=oCallerObject.parentNode.cellIndex;
        var iNextElement=aHiddenColumns.length;
        
        // Zellen der spalte verstecken (außer Kopfzeile)
        
        for (t=1;t<aTable.rows.length;t++)
        {
                aTable.rows[t].cells[iCellIndex].style.overflow='hidden';
                aTable.rows[t].cells[iCellIndex].style.width='20px';
                aTable.rows[t].cells[iCellIndex].style.maxWidth='20px';
                aTable.rows[t].cells[iCellIndex].style.color='transparent';
                aTable.rows[t].cells[iCellIndex].style.whiteSpace='nowrap';
        }

        // legt einen alternativen Header für die versteckte Spalte an

        oNewHeader=document.createElement("td");
        
        oNewHeader_span=document.createElement("span");
        
        oNewHeader_span_attrib1=document.createAttribute("class");
        oNewHeader_span_attrib1.nodeValue="clickable";
        
        oNewHeader_span_attrib2=document.createAttribute("onclick");
        oNewHeader_span_attrib2.nodeValue="showColumn(this,"+iNextElement+")";
        
        oNewHeader_span_text=document.createTextNode("[+]");
        
        oNewHeader_span.setAttributeNode(oNewHeader_span_attrib1);
        oNewHeader_span.setAttributeNode(oNewHeader_span_attrib2);
        oNewHeader_span.appendChild(oNewHeader_span_text);
        
        oNewHeader.appendChild(oNewHeader_span);
        
        // abspeichern des alten Headers
        
        aHiddenColumns.push(aTable.rows[0].cells[iCellIndex].cloneNode(true));
        
        // ersetzen des alten durch den neuen
        
        oCallerObject.parentNode.parentNode.replaceChild(oNewHeader, oCallerObject.parentNode); 
}


function sortTable(oCallerObject)
{

 // optionale Parameter: 'Date' - sortieren einer Datumsspalte, 'AZ' einer Aktenzeichenspalte
 // 'DESC' - absteigend sortieren
 // Reihenfolge egal

 var aSortableTable=new Array();
 var aTransformationArray=new Array();
 var aTempTable=new Array();
 var iCountRows;
 var iSortByColumns;
 var oSortFunc;
 var bSortOrder=0;
 
 // erwartet zwingend ein Datumsformat tt.mm.jjjj !
 
 function DateSort(a,b)
 {
        if ((z=parseInt(a.substr(6,4),10)-parseInt(b.substr(6,4),10))!=0) { return z }
        else if ((z=parseInt(a.substr(3,2),10)-parseInt(b.substr(3,2),10))!=0) { return z }
        else return (parseInt(a.substr(0,2),10)-parseInt(b.substr(0,2),10));
 }
 
 // Aufbau ist AZNR-AZJAHR - AZJAHR ist zweistellig, AZJAHR vor 2000 wäre 9x oder Ähnliches.
 // Da die Software erst 2005 entwickelt wurde, wird der unwahrscheinliche Fall, dass jemand Akten aus
 // <2000 einpflegt nicht berücksichtigt; vielmehr ist davon auszugehen, dass "96" 2096 heißt,
 // was viel wahrscheinlicher ist ;-). Das Programm hat aber ein 2100-Problem *lol*
 
 // ":" ist der angehängte Index, ohne den klappt's nicht
 
 function AZSort(a,b)
 {
         if ((z=parseInt(a.substr(a.indexOf("-")+1,a.indexOf(":")-a.indexOf("-")-1),10)-parseInt(b.substr(b.indexOf("-")+1,b.indexOf(":")-b.indexOf("-")-1),10))!=0) { return z }
               else return (parseInt(a.substr(0,a.indexOf("-")),10)-parseInt(b.substr(0,b.indexOf("-")),10));
 }
 
 if (sortTable.arguments.length>1)
 {
  for (t=1;t<sortTable.arguments.length;t++)
  {
          switch (sortTable.arguments[t])
          {
                case "Date": oSortFunc=DateSort; break; // Datenspalten sortieren
                case "AZ": oSortFunc=AZSort; break; // Aktenzeichenspalte sortieren
                case "DESC": bSortOrder=1; break;
          }
  }       
 
 }
 
 aSortableTable=oCallerObject.parentNode.parentNode.parentNode;
 
 // quasi der FrameBuffer 
 
 aTempTable=aSortableTable.cloneNode(false);
 // aTempTable.appendChild(aSortableTable.getElementsByTagName("tr")[0].cloneNode(true));
 aTempTable.appendChild(aSortableTable.rows[0].cloneNode(true));

 
 iCountRows=aSortableTable.rows.length;
 
 if ((iCountRows<=500) || (iCountRows>500 && confirm("Sie wollen "+iCountRows+" Einträge sortieren ! Dieser Vorgang kann längere Zeit in Anspruch nehmen. Sind Sie sicher ?")))
 {

 // cellIndex - Eigenschaft von td, rowIndex - Eigenschaft von tr
 // parentNode liefert das Elternelement als HTML Object
 // die Knöpfe zum sortieren werden innerhalb eines td Tags aufgenommen. Mit Parentnode wird
 // das einschachtelnde TD element gefunden, mit Cellindex dessen Position innerhalb der Zeile, und damit
 // auch die Spalte, nach der sortiert wird. oCallerObject wird dabei beim Event-Handler der Funktion mittels "this" übergeben
 
 iSortByColumns=oCallerObject.parentNode.cellIndex;

 for (t=0;t<iCountRows-1;t++)
 {
   aTransformationArray[t]=aSortableTable.rows[t+1].cells[iSortByColumns].innerHTML+":"+(t+1);
 }

 if (oSortFunc) { aTransformationArray.sort(oSortFunc); } 
         else { aTransformationArray.sort(); }
 
 if (bSortOrder) { aTransformationArray.reverse(); }
 
 for (iColumn=0;iColumn<iCountRows-1;iColumn++)
 {
        sIndex=aTransformationArray[iColumn];z=parseInt(sIndex.substr(sIndex.indexOf(":")+1));
//         aTempTable.appendChild(aSortableTable.getElementsByTagName("tr")[z].cloneNode(true));
        aTempTable.appendChild(aSortableTable.rows[z].cloneNode(true));

 }

 // damit wird alles im Bereich <table> .. </table> überschrieben. Felder wie <input type=hidden> o.ä. für skriptrelevante Aufgaben verschwinden !

 aSortableTable.parentNode.replaceChild(aTempTable, aSortableTable);
 
 }
}
