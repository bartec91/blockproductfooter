$(document).ready(function(){
	const textToExchange = blockproductfooter_exchange_text;
	const elementToExchange = document.querySelector("div:nth-child(3) section.page-product-box h3.page-product-heading");

	elementToExchange.textContent = `${textToExchange}`;
});