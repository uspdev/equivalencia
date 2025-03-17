<div class="modal fade" id="transition-modal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabel">Confirmar Transição</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="formContent">
        <p>Carregando formulário...</p>
      </div> 
    </div>
  </div>
</div>

@once
  @section('javascripts_bottom')
    @parent
    <script>
      $(document).ready(function() {
        var transitionModal = $('#transition-modal');
        var formContent = $('#formContent');

        $('.transition-btn').on('click', function() {
          var transitionName = $(this).data('transition');
          var transitionUrl = $(this).data('url');
          var workflowName = $(this).data('workflow');

          let forms = @json($workflowObjectData['forms']);
          let selectedForm = forms.find(f => f.transition === transitionName);

          if (selectedForm) {
            formContent.html(selectedForm.html);
            transitionModal.modal('show');
          } else if (transitionUrl) {
            $.ajax({
              url: transitionUrl,
              type: 'POST',
              data: {
                _token: '{{ csrf_token() }}',
                transition: transitionName,
                workflowDefinitionName: workflowName
              },
              success: function(response) {
                location.reload();
              },
              error: function(xhr) {
                alert('Erro ao processar a transição: ' + xhr.responseText);
              }
            });
          }
        });
      });
    </script>
  @endsection
@endonce
