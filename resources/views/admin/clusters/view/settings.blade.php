@extends('layouts.admin')

@section('title')
    {{ $node->name }}: Settings
@endsection

@section('content-header')
    <h1>{{ $node->name }}<small>Configure your cluster settings.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.clusters') }}">Clusters</a></li>
        <li><a href="{{ route('admin.clusters.view', $node->id) }}">{{ $node->name }}</a></li>
        <li class="active">Settings</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.clusters.view', $node->id) }}">About</a></li>
                <li class="active"><a href="{{ route('admin.clusters.view.settings', $node->id) }}">Settings</a></li>
                <li><a href="{{ route('admin.clusters.view.configuration', $node->id) }}">Configuration</a></li>
            </ul>
        </div>
    </div>
</div>
<form action="{{ route('admin.clusters.view.settings', $node->id) }}" method="POST">
    <div class="row">
        <div class="col-sm-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Settings</h3>
                </div>
                <div class="box-body row">
                    <div class="form-group col-xs-12">
                        <label for="name" class="control-label">Node Name</label>
                        <div>
                            <input type="text" autocomplete="off" name="name" class="form-control" value="{{ old('name', $node->name) }}" />
                            <p class="text-muted"><small>Character limits: <code>a-zA-Z0-9_.-</code> and <code>[Space]</code> (min 1, max 100 characters).</small></p>
                        </div>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="description" class="control-label">Description</label>
                        <div>
                            <textarea name="description" id="description" rows="4" class="form-control">{{ $node->description }}</textarea>
                        </div>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="name" class="control-label">Location</label>
                        <div>
                            <select name="location_id" class="form-control">
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ (old('location_id', $node->location_id) === $location->id) ? 'selected' : '' }}>{{ $location->long }} ({{ $location->short }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="public" class="control-label">Cluster Visibility</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" name="public" value="1" {{ (old('public', $node->public)) ? 'checked' : '' }} id="public_1" checked> <label for="public_1" style="padding-left:5px;">Public</label>
                            </div>
                            <div class="radio radio-danger radio-inline">
                                <input type="radio" name="public" value="0" {{ (old('public', $node->public)) ? '' : 'checked' }} id="public_0"> <label for="public_0" style="padding-left:5px;">Private</label>
                            </div>
                        </div>
                        <p class="text-muted small">By setting a cluster to <code>private</code> you will be denying the ability to auto-deploy to this cluster.
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="fqdn" class="control-label">Fully Qualified Domain Name</label>
                        <div>
                            <input type="text" autocomplete="off" name="fqdn" class="form-control" value="{{ old('fqdn', $node->fqdn) }}" />
                        </div>
                        <p class="text-muted"><small>Please enter domain name (e.g <code>node.example.com</code>) to be used for connecting to the daemon. An IP address may only be used if you are not using SSL for this node.
                                <a tabindex="0" data-toggle="popover" data-trigger="focus" title="Why do I need a FQDN?" data-content="In order to secure communications between your server and this node we use SSL. We cannot generate a SSL certificate for IP Addresses, and as such you will need to provide a FQDN.">Why?</a>
                            </small></p>
                    </div>
                    <div class="form-group col-xs-12">
                        <label class="form-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> Communicate Over SSL</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pSSLTrue" value="https" name="scheme" {{ (old('scheme', $node->scheme) === 'https') ? 'checked' : '' }}>
                                <label for="pSSLTrue"> Use SSL Connection</label>
                            </div>
                            <div class="radio radio-danger radio-inline">
                                <input type="radio" id="pSSLFalse" value="http" name="scheme" {{ (old('scheme', $node->scheme) !== 'https') ? 'checked' : '' }}>
                                <label for="pSSLFalse"> Use HTTP Connection</label>
                            </div>
                        </div>
                        <p class="text-muted small">In most cases you should select to use a SSL connection. If using an IP Address or you do not wish to use SSL at all, select a HTTP connection.</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <label class="form-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> Behind Proxy</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pProxyFalse" value="0" name="behind_proxy" {{ (old('behind_proxy', $node->behind_proxy) == false) ? 'checked' : '' }}>
                                <label for="pProxyFalse"> Not Behind Proxy </label>
                            </div>
                            <div class="radio radio-info radio-inline">
                                <input type="radio" id="pProxyTrue" value="1" name="behind_proxy" {{ (old('behind_proxy', $node->behind_proxy) == true) ? 'checked' : '' }}>
                                <label for="pProxyTrue"> Behind Proxy </label>
                            </div>
                        </div>
                        <p class="text-muted small">If you are running the daemon behind a proxy such as Cloudflare, select this to have the daemon skip looking for certificates on boot.</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <label class="form-label"><span class="label label-warning"><i class="fa fa-wrench"></i></span> Maintenance Mode</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pMaintenanceFalse" value="0" name="maintenance_mode" {{ (old('behind_proxy', $node->maintenance_mode) == false) ? 'checked' : '' }}>
                                <label for="pMaintenanceFalse"> Disabled</label>
                            </div>
                            <div class="radio radio-warning radio-inline">
                                <input type="radio" id="pMaintenanceTrue" value="1" name="maintenance_mode" {{ (old('behind_proxy', $node->maintenance_mode) == true) ? 'checked' : '' }}>
                                <label for="pMaintenanceTrue"> Enabled</label>
                            </div>
                        </div>
                        <p class="text-muted small">If the node is marked as 'Under Maintenance' users won't be able to access servers that are on this node.</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <label for="disk_overallocate" class="control-label">Maximum Web Upload Filesize</label>
                        <div class="input-group">
                            <input type="text" name="upload_size" class="form-control" value="{{ old('upload_size', $node->upload_size) }}"/>
                            <span class="input-group-addon">MiB</span>
                        </div>
                        <p class="text-muted"><small>Enter the maximum size of files that can be uploaded through the web-based file manager.</small></p>
                    </div>
                    <div class="col-xs-12">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="daemonListen" class="control-label"><span class="label label-warning"><i class="fa fa-power-off"></i></span> Daemon Port</label>
                                <div>
                                    <input type="text" name="daemonListen" class="form-control" value="{{ old('daemonListen', $node->daemonListen) }}"/>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <p class="text-muted small">If you will be running the daemon behind CloudFlare® you should set the daemon port to <code>8443</code> to allow websocket proxying over SSL.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Cluster Configuration</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="pHost" class="form-label">Host</label>
                        <input type="text" name="host" id="pHost" class="form-control" value="{{ old('host', $node->host) }}"/>
                        <p class="text-muted small">Host must be a host string, a <code>host:port</code> pair, or a <em>URL</em> to the base of the apiserver.</p>
                    </div>
                    <div class="form-group">
                        <label for="pBearerToken" class="form-label">Bearer Token</label>
                        <textarea name="bearer_token" id="pBearerToken" rows="16" class="form-control"></textarea>
                        <p class="text-muted small">Service account bearer tokens are perfectly valid to use outside the cluster and can be used to create identities for long standing jobs that wish to talk to the Kubernetes API.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Insecure</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pInsecureFalse" value="0" name="insecure" {{ (old('insecure', $node->insecure) == true) ? 'checked' : '' }}>
                                <label for="pInsecureFalse"> False</label>
                            </div>
                            <div class="radio radio-danger radio-inline">
                                <input type="radio" id="pInsecureTrue" value="1" name="insecure" {{ (old('insecure', $node->insecure) == true) ? 'checked' : '' }}>
                                <label for="pInsecureTrue"> True</label>
                            </div>
                        </div>
                        <p class="text-muted small">Server should be accessed without verifying the TLS certificate. For testing only.</p>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="pServiceType" class="form-label">Service Type</label>
                            <select name="service_type" id="pServiceType">
                                <option value="nodeport" {{ (old('service_type', $node->service_type) == 'nodeport') ? 'selected' : '' }}>NodePort</option>
                                <option value="loadbalancer" {{ (old('service_type', $node->service_type) == 'loadbalancer') ? 'selected' : '' }}>LoadBalancer</option>
                            </select>
                            <p class="text-muted small">ServiceTypes allow you to specify what kind of Service you want.</p>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="pStorageClass" class="form-label">Storage Class</label>
                            <input type="text" name="storage_class" id="pStorageClass" class="form-control" value="{{ old('storage_class', $node->storage_class) }}"/>
                            <p class="text-muted small">StorageClass provides a way for administrators to describe the "classes" of storage they offer.</p>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="pNamespace" class="form-label">Namespace</label>
                            <input type="text" name="ns" data-multiplicator="true" class="form-control" id="pNamespace" value="{{ old('ns', $node->ns) }}"/>
                            <p class="text-muted small">Namespaces provides a mechanism for isolating groups of resources within a single cluster.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Save Settings</h3>
                </div>
                <div class="box-body row">
                    <div class="form-group col-sm-6">
                        <div>
                            <input type="checkbox" name="reset_secret" id="reset_secret" /> <label for="reset_secret" class="control-label">Reset Daemon Master Key</label>
                        </div>
                        <p class="text-muted"><small>Resetting the daemon master key will void any request coming from the old key. This key is used for all sensitive operations on the daemon including server creation and deletion. We suggest changing this key regularly for security.</small></p>
                    </div>
                </div>
                <div class="box-footer">
                    {!! method_field('PATCH') !!}
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-primary pull-right">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#pBearerToken').val('{{ old('bearer_token', $node->bearer_token) }}');

    $('[data-toggle="popover"]').popover({
        placement: 'auto'
    });
    $('select[name="location_id"]').select2();
    $('select[name="service_type"]').select2();
    </script>
@endsection
