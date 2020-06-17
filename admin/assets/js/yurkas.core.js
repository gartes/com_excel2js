var yurkasCore = function ()
{
    var $ = jQuery ;
    var self = this ;

    this.max_count_part_file = false ;

    this.INIT = function ()
    {
        var $form = $('#upload_form_yurkas');
        // $form.find('')
        $form.on('submit.yurkasCore' ,  self.myFormSubmit)

    }
    /**
     * Отправка данных формы
     * @param $form
     */
    this.formProcess = function ($form)
    {
        var data = $form.serialize();
        wgnz11.getModul("Ajax").then(function (Ajax) {

            // Не обрабатывать сообщения
            Ajax.ReturnRespond = true ;
            // Отправить запрос
            Ajax.send(data).then(function (r) {
                if ( !r.success ) {
                    if ( r.message ){
                        alert( r.message );
                        return ;
                    }else {
                        alert('Err Data');
                        return ;
                    }
                }
                $.each(r.data.forms , function ( i, a )
                {
                    $form.find('[name="'+i+'"]').val( a ) ;
                })

                // Сохранить огрвничение по количеству листов
                self.max_count_part_file = +r.data.max_count_part_file ;

                if (self.formControl($form) ) {


                    self.formProcess( $form )
                }else{
                    alert('All Ok!!!');
                    return;
                }

                console.log(r)
            },function(err) {
                console.error(err)
            })
        });
    }
    this.formControl = function ($form)
    {
        var part = +$form.find('[name="part"]').val();
        var all_count_price = +$form.find('[name="all_count_price"]').val();
        var rows_processed = +$form.find('[name="rows_processed"]').val();

        console.log( part )
        console.log( all_count_price )
        console.log( rows_processed )
        console.log( self.max_count_part_file )
        console.log( part )
        if ( !part ) return true ;




        // Если включено ограничение по колчеству файлов ( частей )
        // и оно равно количеству - то стоп !
        if (self.max_count_part_file === part ) {
            return false ;
        }

        if (all_count_price ===  rows_processed ) return false ;



        return true ;
    }
    /**
     * Запуска Ajax отправки данных
     * @param event
     */
    this.myFormSubmit = function (event)
    {
        event.preventDefault();
        var $form = $(this);
        if (self.formControl ) self.formProcess( $form )
    }

};
(function ()
{
    var yCore = new yurkasCore();
    yCore.INIT();
})();

