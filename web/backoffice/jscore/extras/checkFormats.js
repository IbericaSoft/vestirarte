/**
*	Script para validar formatos de campos
*   @author  DobleH Software Antonio G·mez :: agamez@dobleh.com
*	@version 1.2								
*/

/**
 *  Comprueba si el email tiene un formato valido 
 *  */
function esEmail(mail){
	var email=/^[Ò—\w-_\.]+@[Ò—\w]{1}[Ò—\w-_\.]*[\.]{1}[Ò—\w]{2,3}$/;
	return email.test(mail);
}

//alert(123456789.67392).toMoney()
Number.prototype.toMoney = function(decimals, decimal_sep, thousands_sep){ 
   var n = this,
   c = isNaN(decimals) ? 2 : Math.abs(decimals), //if decimal is zero we must take it, it means user does not want to show any decimal
   d = decimal_sep || ',', //if no decimal separetor is passed we use the comma as default decimal separator (we MUST use a decimal separator)

   /*
   according to [http://stackoverflow.com/questions/411352/how-best-to-determine-if-an-argument-is-not-sent-to-the-javascript-function]
   the fastest way to check for not defined parameter is to use typeof value === 'undefined' 
   rather than doing value === undefined.
   */   
   t = (typeof thousands_sep === 'undefined') ? '.' : thousands_sep, //if you don't want ot use a thousands separator you can pass empty string as thousands_sep value

   sign = (n < 0) ? '-' : '',

   //extracting the absolute value of the integer part of the number and converting to string
   i = parseInt(n = Math.abs(n).toFixed(c)) + '', 

   j = ((j = i.length) > 3) ? j % 3 : 0; 
   return sign + (j ? i.substr(0, j) + t : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : ''); 
}

