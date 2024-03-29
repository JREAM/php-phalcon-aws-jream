{% if not session.has('is_logged_in') and 10 == 1 %}

<a data-toggle="modal" href="#modalLogin" class="btn btn-primary btn-lg">Launch demo modal</a>

<div id="modalLogin" class="modal modal-dialog">
  <div class="modal-content">
      <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
          <h4 class="modal-title" id="myModalLabel">JREAM Login</h4>
      </div>
      <div class="modal-body">
          <div class="row">
              <div class="col-xs-6">
                  <div class="well">
                      <form id="loginForm" method="POST" action="{{ url('auth/api/login') }}" novalidate="novalidate">
                          <div class="form-group">
                              <label for="username" class="control-label">Username</label>
                              <input type="text" class="form-control" id="username" name="username" value="" required="" title="Please enter you username" placeholder="example@gmail.com">
                              <span class="help-block"></span>
                          </div>
                          <div class="form-group">
                              <label for="password" class="control-label">Password</label>
                              <input type="password" class="form-control" id="password" name="password" value="" required="" title="Please enter your password">
                              <span class="help-block"></span>
                          </div>
                          <div id="loginErrorMsg" class="alert alert-error hide">Wrong username og password</div>
                          <button type="submit" class="btn btn-success btn-block">Login</button>
                          <a href="{{ url('user/') }}/forgot/" class="btn btn-default btn-block">Help to login</a>
                      </form>
                  </div>
              </div>
              <div class="col-xs-6">
                  <p class="lead">Register now for <span class="text-success">FREE</span></p>
                  <ul class="list-unstyled" style="line-height: 2">
                      <li><span class="fa fa-check text-success"></span> See all your orders</li>
                      <li><span class="fa fa-check text-success"></span> Fast re-order</li>
                      <li><span class="fa fa-check text-success"></span> Save your favorites</li>
                      <li><span class="fa fa-check text-success"></span> Fast checkout</li>
                      <li><span class="fa fa-check text-success"></span> Get a gift <small>(only new customers)</small></li>
                      <li><a href="/read-more/"><u>Read more</u></a></li>
                  </ul>
                  <p><a href="/new-customer/" class="btn btn-info btn-block">Yes please, register now!</a></p>
              </div>
          </div>
      </div>
  </div>
</div>

{% endif %}
