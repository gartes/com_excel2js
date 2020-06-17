window.impotToLine = function () {
    var $ = jQuery ;
    var self = this ;

    this.Url = '/index.php?option=com_excel2js&task=importLines'
    /**
     * Хранение истории импорта
     * @type {*[]}
     */
    this.historyFile = [] ;
    /**
     * счетчик строк массива this.Lines
     * @type {number}
     */
    this.counterLine = 0 ;
    /**
     * массив системных строк для добавления
     * @type {*[]}
     */
    this.Lines = [] ;
    this.FileName ;
    /**
     * Количество строк в this.Lines
     * @type {number}
     */
    this.lineInFile = 0 ;
    this.ajaxImportSubmit = function ($form) {
        $uploaded_file = $form.find('[name="uploaded_file[]"]:checked');
        if ( !$uploaded_file.length ) {
            alert('Выбирите файлы для импорта')
            return false;
        }
        this.FileName = $uploaded_file.val();



        var data = $form.serializeArray();
        console.log( data ) ;
        this.getModul("Ajax").then(function (Ajax) {
            // Не обрабатывать сообщения
            Ajax.ReturnRespond = true ;
            // Отправить запрос
            Ajax.send(data).then(function (r) {
                if ( r.data.length ){
                    self.Lines = r.data ;
                    self.lineInFile =  self.Lines.length ;
                    if (!self.lineInFile) return ;
                    // Импорт одной строки
                    console.log( 'Будет загружено ' + self.lineInFile + ' товаров') ;
                    self.importLine() ;
                }
            },function(err) {
                console.error(err)
            });
        });
    }
    /**
     * Сохранить Json log на сервере
     */
    this.saveLogDataServer = function () {

    }
    /**
     * Импорт одной строки
     * @param Lines - array - массив объектов
     */
    this.importLine = function () {
        var data = {
            task : 'importLines' ,
            line : self.Lines[ this.counterLine ] ,
            option : 'com_excel2js' ,
        }
        this.getModul("Ajax").then(function (Ajax) {
            // Не обрабатывать сообщения
            Ajax.ReturnRespond = true ;
            // Отправить запрос
            Ajax.send(data).then(function (r) {
                if ( !r.success ) {
                    console.error( 'line:'+self.counterLine , r );
                }
                // Добавить в лог
                self.addHistory(r.data);
                //
                self.controllerTask('importLine');
                console.log(r)
            },function(err) {
                console.error(err)
            })
        });
    }
    this.controllerTask = function ( task ) {
        switch (task) {
            case 'importLine' :
                if(this.lineInFile > self.counterLine ){
                    console.log( 'Количество обработанных строк ' + self.counterLine );
                    console.log( 'Обрабатываемый файл: '+this.FileName)
                    self.counterLine++;
                    self.importLine();
                }else {

                }
                console.log( this.lineInFile );
                break;
        }
    }

    this.addHistory = function ( itemProduct ) {
        this.historyFile.push( itemProduct )
    }
    this.Init = function () {}

    this.Init();
}
window.impotToLine.prototype = new GNZ11();
new window.impotToLine();



