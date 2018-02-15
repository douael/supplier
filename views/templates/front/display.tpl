{**
* 2010-2017 EcomiZ
*
*  @author    EcomiZ
*  @copyright 2010-2017
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  @version  Release: 1.3.0 $Revision: 1 $
*}

{capture name=path}{$title|escape:'htmlall':'UTF-8'}{/capture}

<h1>{$title|escape:'html'}</h1>
<div class="main row">
{foreach from=$data item=article key=k}
<div style="{if $float == 0}clear:left;{else}float:left;{/if}margin-left:10px;">
    <div class="main row noflottant">
        <div class="view view-third col-md-3">
        <img width="{$width|escape:'html'}" height="{$height|escape:'html'}" alt="" src="{$article['path_mini']|escape:'html'}">
        <div class="mask" style="width:{$width|escape:'html'}px; height:{$height|escape:'html'}px">
            <div style="display:{if $display_legend == 0 }none{else}block{/if}">
            <h2>{$article['name_article']|escape:'html'}</h2>
            <h3>{$article['date_article']|date_format:"%d/%m/%Y"|escape:'htmlall':'UTF-8'}</h3>
            <p>{$article['desc_article']|escape:'html'}</p>
            </div>
            <a class="zoom fancybox" data-fancybox-group="other-views" href="{$article['path_article']|escape:'html'}">Zoom</a>
            
        </div>
        </div>
    </div>
    
</div>
{/foreach}
</div>