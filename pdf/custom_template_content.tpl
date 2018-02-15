{*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<div style="font-size:0.9em">
{foreach from=$custom_model.orders item=order name=orders}
<table style="width: 100%;padding-top:5px;">
	<tr>
		<td style="text-align: right; width:100%;">
			{$custom_model.address_supplier}
			<br/>
		</td>
	</tr>
</table>
<table style="width: 100%;padding-top:5px;">
	<tr>
		<td style="text-align: left; width:50%;">
			<h4>{l s="VOTRE CONTACT" pdf='true'} :</h4>
			{$custom_model.shop_address}
		</td>
		<td style="width:50%;border:1px solid #000;">
			<h4>{l s="DESTINATAIRE" pdf='true'} :</h4>
				{foreach from=$order.dest item=dest}
					{$dest}<br/>
				{/foreach}
		</td>
	</tr>
</table>
<br/>
<table style="width: 100%;margin-bottom:10px;padding-top:5px;">
	<tr>
		<td>
			<h3 style="text-align:center;">{$order.title}</h3>
			<h4 style="text-align:center;">{if $order.reference != ""}Référence : {$order.reference} {/if}Date : {$order.order_date|date_format:"%d/%m/%Y"}</h4>
			<br/>
		</td>
	</tr>
	<tr>
		<td>
			<p>{l s="Nous vous prions de bien vouloir trouver ci-dessous notre commande d'approvisionnement. Merci de nous confirmer par retour de mail (adv.ecommerce@cider.fr) son bon enregistrement." pdf='true'}</p>
			<p>{l s="Please find our order attached. We would appreciate a confirmation from you per e-mail, addressed to adv.ecommerce@cider.fr Thank you." pdf='true'}</p>
			<p>{l s="Vi preghiamo gentilmente di seguito troverete il nostro ordine di fornitura. Grazie per confermarci giro di posta il suo buon record." pdf='true'}</p>
			<p>{l s="Guten Tag, Anbei finden Sie unsere Bestellung. Bitte senden Sie uns Ihre Bestätigung per E-Mail an folgende Adresse: adv.ecommerce@cider.fr  .Vielen Dank im Voraus." pdf='true'}</p>
		</td>
	</tr>
</table>
<br/>
<table style="width: 100%; border:1px solid #000;padding-top:5px;">
	<tr style="margin-bottom:10px;">
		<td>
			<h4>{l s="COMMANDE" pdf='true'} :</h4>
			<ul style="list-style-type:none;">
				{foreach from=$order.cmd item=cmd}
					<li>{$cmd}</li>
				{/foreach}
			</ul>
			<br/>
		</td>
	</tr>
</table>
<br/>
<table style="width: 100%;padding-top:5px;">
	<tr>
		<td>
			{$custom_model.modalites}
		</td>
	</tr>
</table>
	{if $smarty.foreach.orders.last}
		<br/>
	{else}
		<br pagebreak="true"/>
	{/if}
{/foreach}
</div>

