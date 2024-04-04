function afftr()
{
    if(document.getElementById('iosoui').checked == true)
    {
	document.getElementById('sound').style.display = "inline";
	document.getElementById('badge').style.display = "inline";
	document.getElementById('acme').style.display = "inline";
    }
    else
    {
	document.getElementById('sound').style.display = "none";
	document.getElementById('badge').style.display = "none";
	document.getElementById('acme').style.display = "none";
    }
}
