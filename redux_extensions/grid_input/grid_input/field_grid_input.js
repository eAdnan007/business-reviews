jQuery(function($){
  setup_set();
  
  show_hide_conditional();
  
  $('.multi-input-holder').addInputArea({
    area_var: '.input_set',
    btn_add: '.add-input-set',
    btn_del: '.remove-input-set',
    after_add: function(){
      accordion_refresh();
      show_hide_conditional()
    }
  });
  
  $('.multi-input-holder').
  on( 'click', '.remove-input-set', function(){
    accordion_refresh();
    show_hide_conditional();
  } ).
  on( 'change', '.field_type, .question_type', show_hide_conditional );
  
  function accordion_refresh(){
     $(".multi-input-holder").accordion("refresh");
     
     var i = 0;
     $('.multi-input-holder .input_set').each(function(){
       $(this).find('.id_holder').text(i++);
     });
     
     return;
  }
    
  function setup_set(){
    $(".multi-input-holder").accordion({
      header: "> fieldset > h3",
      heightStyle: "content"
    });
  }
  
  function show_hide_conditional(){
    $('.multi-input-holder .input_set').each(function(){
      var set = this;
      if($(set).find('.field_type').val() == 'question'){
        $(set).find('.field-type-question').show();
        $(set).find('.field-type-rating').hide();
      }
      else {
        $(set).find('.field-type-question').hide();
        $(set).find('.field-type-rating').show();
      }
      
      if($(set).find('.question_type').val() == 'select'){
        $(set).find('.question-type-select').show();
      }
      else {
        $(set).find('.question-type-select').hide();
      }
    });
  }
});