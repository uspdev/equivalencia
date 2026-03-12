{{-- Script de comportamento dos botões de transição.
     Ao clicar em um botão transition-btn:
     - Se existir formulário para a transição: exibe o formulário e rola até ele.
     - Se não houver formulário: aplica a transição via POST/AJAX. --}}
@once
    @section('javascripts_bottom')
        @parent
        <script>
            $(document).ready(function () {
                $('.transition-btn').on('click', function (e) {
                    var transitionName = $(this).data('transition');
                    var transitionUrl  = $(this).data('url');
                    var workflowName   = $(this).data('workflow');
                    var formsContainer = $('#transition-forms-container');
                    var formWrapper    = $('.inline-transition-form[data-transition="' + transitionName + '"]');

                    if (formWrapper.length > 0) {
                        e.preventDefault();

                        var transitionForm = formWrapper.find('form').first();
                        if (transitionForm.length === 0) {
                            return;
                        }

                        $('.inline-transition-form').addClass('d-none');
                        formsContainer.show();
                        formWrapper.removeClass('d-none');

                        if (transitionForm.find('input[name="transition"]').length === 0) {
                            transitionForm.append('<input type="hidden" name="transition" value="' + transitionName + '">');
                        } else {
                            transitionForm.find('input[name="transition"]').val(transitionName);
                        }

                        if (transitionForm.find('input[name="workflowDefinitionName"]').length === 0 && workflowName) {
                            transitionForm.append('<input type="hidden" name="workflowDefinitionName" value="' + workflowName + '">');
                        }

                        $('html, body').animate({ scrollTop: formsContainer.offset().top - 20 }, 200);
                        return;
                    }

                    if (transitionUrl) {
                        e.preventDefault();
                        $.ajax({
                            url: transitionUrl,
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                transition: transitionName,
                                workflowDefinitionName: workflowName
                            },
                            success: function () { location.reload(); },
                            error: function (xhr) {
                                alert('Erro ao processar a transição: ' + xhr.responseText);
                            }
                        });
                    }
                });
            });
        </script>
    @endsection
@endonce
