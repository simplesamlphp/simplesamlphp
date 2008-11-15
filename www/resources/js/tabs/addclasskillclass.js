//*** This code is copyright 2002-2003 by Gavin Kistner and Refinery; www.refinery.com
//*** It is covered under the license viewable at http://phrogz.net/JS/_ReuseLicense.txt
//*** Reuse or modification is free provided you abide by the terms of that license.
//*** (Including the first two lines above in your source code satisfies the conditions.)

//***Adds a new class to an object, preserving existing classes
function AddClass(obj,cName){ KillClass(obj,cName); return obj && (obj.className+=(obj.className.length>0?' ':'')+cName); }

//***Removes a particular class from an object, preserving other existing classes.
function KillClass(obj,cName){ return obj && (obj.className=obj.className.replace(new RegExp("^"+cName+"\\b\\s*|\\s*\\b"+cName+"\\b",'g'),'')); }

//***Returns true if the object has the class assigned, false otherwise.
function HasClass(obj,cName){ return (!obj || !obj.className)?false:(new RegExp("\\b"+cName+"\\b")).test(obj.className) }

