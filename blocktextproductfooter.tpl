<section class="page-product-box">
	{* <h3 class="page-product-heading"></h3> *}
	<!-- full description -->
	<div class="rte">
		{if isset($blockproductfooter_img)}
			<img class="img-responsive" src="{$blockproductfooter_img|escape:'htmlall':'UTF-8'}" alt="{$blockproductfooter_desc|escape:'htmlall':'UTF-8'}" title="{$blockproductfooter_desc|escape:'htmlall':'UTF-8'}" width="500" height="500" />
		{/if}
		<p>
			{if isset($blockproductfooter_desc)}
				{$blockproductfooter_desc|escape:'htmlall':'UTF-8'}
			{/if}
		</p>
	</div>
</section>
