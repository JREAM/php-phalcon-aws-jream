$(() => {

  $("#formQuestionDelete").submit(function (evt) {
    evt.preventDefault();

    const url = $(this).attr("action");

    $(this).get(url, postData, function (resp) {
      console.log(resp);
    }, "json");
  });

});
