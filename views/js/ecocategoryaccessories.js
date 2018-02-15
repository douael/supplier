$(document).ready(function(){
//our function wrapper.
		var initMyAssociationsAutocomplete = function (){
//initialize the autocomplete that will point to the default ajax_products_list page (it returns the products by id+name)
			$('#product_autocomplete_input_association')
					.autocomplete('ajax_products_list_old.php', {
						minChars: 1,
						autoFill: true,
						max:20,
						matchContains: true,
						mustMatch:true,
						scroll:false,
						cacheLength:0,
						formatItem: function(item) {
							return item[1]+' - '+item[0];
						}
					}).result(addAssociation);
//as an option we will add a function to exclude a product if it's already in the list
			$('#product_autocomplete_input_association').setOptions({
				extraParams: {
					excludeIds : getAssociationsIds()
				}
			});
		};
//function to exclude a product if it exists in the list
		var getAssociationsIds = function()
		{
			if ($('#id_product').val() === undefined)
				return '';
			return $('#id_product').val().replace(/\-/g,',');
		}
//function to add a new association, adds it in the hidden input and also as a visible div, with a button to delete the association any time.
		var addAssociation = function(event, data, formatted)
		{
			if (data == null)
				return false;
			var productId = data[1];
			var productName = data[0];

			var $divAccessories = $('#divAccessories');
			var $inputAccessories = $('#id_product');
			var $nameAccessories = $('#nameMyAssociations');

			/* delete product from select + add product line to the div, input_name, input_ids elements */
			$divAccessories.html('<div class="form-control-static"><button type="button" class="delAssociation btn btn-default" name="' + productId + '"><i class="icon-remove text-danger"></i></button>&nbsp;'+ productName +'</div>');
			$nameAccessories.val(productName);
			$inputAccessories.val(productId);
			$('#product_autocomplete_input_association').val('');
			$('#product_autocomplete_input_association').setOptions({
				extraParams: {
					excludeIds : getAssociationsIds()
				}
			});
		};
//the function to delete an associations, delete it from both the hidden inputs and the visible div list.
		var delAssociations = function(id)
		{
			var div = getE('divAccessories');
			var input = getE('id_product');
			var name = getE('nameMyAssociations');

			// Cut hidden fields in array
			var inputCut = input.value.split('-');
			var nameCut = name.value.split('¤');

			if (inputCut.length != nameCut.length)
				return alert('Bad size');

			// Reset all hidden fields
			input.value = '-';
			name.value = '¤';
			div.innerHTML = '';
			for (i in inputCut)
			{
				// If empty, error, next
				if (!inputCut[i] || !nameCut[i])
					continue ;

				// Add to hidden fields no selected products OR add to select field selected product
				if (inputCut[i] != id)
				{
					input.value += inputCut[i] + '-';
					name.value += nameCut[i] + '¤';
					div.innerHTML += '<div class="form-control-static"><button type="button" class="delAssociation btn btn-default" name="' + inputCut[i] +'"><i class="icon-remove text-danger"></i></button>&nbsp;' + nameCut[i] + '</div>';
				}
				else
					$('#selectAssociation').append('<option selected="selected" value="' + inputCut[i] + '-' + nameCut[i] + '">' + inputCut[i] + ' - ' + nameCut[i] + '</option>');
			}

		   $('#product_autocomplete_input_association').setOptions({
				extraParams: {
					excludeIds : getAssociationsIds()
				}
			});
		};

		//finally initialize the function we have written above and create all the binds.
			initMyAssociationsAutocomplete();
		//live delegation of the deletion button to our delete function, this will allow us to delete also any element added after the dom creation with the ajax autocomplete.
			$('#divAccessories').delegate('.delAssociation', 'click', function(){
				delAssociations($(this).attr('name'));
			});
	});