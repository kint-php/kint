$(document).ready(function() {
	window.prettyPrint && prettyPrint();

    var downloadLink = $('a[href$="kint.phar"]');

    downloadLink.prop('href', downloadLink.prop('href') + '?' + new Date().getTime());
});
