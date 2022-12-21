@extends('layouts.admin')

@section('title')
    Clusters &rarr; New
@endsection

@section('content-header')
    <h1>New Cluster<small>Create a new local or remote cluster for servers to be installed to.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.clusters') }}">Clusters</a></li>
        <li class="active">New</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.clusters.new') }}" method="POST">
    <div class="row">
        <div class="col-sm-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Daemon Details</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="pName" class="form-label">Name</label>
                        <input type="text" name="name" id="pName" class="form-control" value="{{ old('name') }}"/>
                        <p class="text-muted small">Character limits: <code>a-zA-Z0-9_.-</code> and <code>[Space]</code> (min 1, max 100 characters).</p>
                    </div>
                    <div class="form-group">
                        <label for="pDescription" class="form-label">Description</label>
                        <textarea name="description" id="pDescription" rows="4" class="form-control">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="pLocationId" class="form-label">Location</label>
                        <select name="location_id" id="pLocationId">
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ $location->id != old('location_id') ?: 'selected' }}>{{ $location->short }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cluster Visibility</label>
                        <div>
                            <div class="radio radio-success radio-inline">

                                <input type="radio" id="pPublicTrue" value="1" name="public" checked>
                                <label for="pPublicTrue"> Public </label>
                            </div>
                            <div class="radio radio-danger radio-inline">
                                <input type="radio" id="pPublicFalse" value="0" name="public">
                                <label for="pPublicFalse"> Private </label>
                            </div>
                        </div>
                        <p class="text-muted small">By setting a cluster to <code>private</code> you will be denying the ability to auto-deploy to this cluster.
                    </div>
                    <div class="form-group">
                        <label for="pFQDN" class="form-label">FQDN</label>
                        <input type="text" name="fqdn" id="pFQDN" class="form-control" value="{{ old('fqdn') }}"/>
                        <p class="text-muted small">Please enter domain name (e.g <code>daemon.example.com</code>) to be used for connecting to the daemon. An IP address may be used <em>only</em> if you are not using SSL for this daemon.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Communicate Over SSL</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pSSLTrue" value="https" name="scheme" checked>
                                <label for="pSSLTrue"> Use SSL Connection</label>
                            </div>
                            <div class="radio radio-danger radio-inline">
                                <input type="radio" id="pSSLFalse" value="http" name="scheme" @if(request()->isSecure()) disabled @endif>
                                <label for="pSSLFalse"> Use HTTP Connection</label>
                            </div>
                        </div>
                        @if(request()->isSecure())
                            <p class="text-danger small">Your Panel is currently configured to use a secure connection. In order for browsers to connect to your daemon it <strong>must</strong> use a SSL connection.</p>
                        @else
                            <p class="text-muted small">In most cases you should select to use a SSL connection. If using an IP Address or you do not wish to use SSL at all, select a HTTP connection.</p>
                        @endif
                    </div>
                    <div class="form-group">
                        <label class="form-label">Behind Proxy</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pProxyFalse" value="0" name="behind_proxy" checked>
                                <label for="pProxyFalse"> Not Behind Proxy </label>
                            </div>
                            <div class="radio radio-info radio-inline">
                                <input type="radio" id="pProxyTrue" value="1" name="behind_proxy">
                                <label for="pProxyTrue"> Behind Proxy </label>
                            </div>
                        </div>
                        <p class="text-muted small">If you are running the daemon behind a proxy such as Cloudflare, select this to have the daemon skip looking for certificates on boot.</p>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="pDaemonListen" class="form-label">Daemon Port</label>
                            <input type="text" name="daemonListen" class="form-control" id="pDaemonListen" value="8080" />
                        </div>
                        <div class="col-md-12">
                            <p class="text-muted small">If you will be running the daemon behind CloudFlare&reg; you should set the daemon port to <code>8443</code> to allow websocket proxying over SSL.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Cluster Configuration</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="pHost" class="form-label">Host</label>
                        <input type="text" name="host" id="pHost" class="form-control" value="{{ old('host') }}"/>
                        <p class="text-muted small">Host must be a host string, a <code>host:port</code> pair, or a <em>URL</em> to the base of the apiserver.</p>
                    </div>
                    <div class="form-group">
                        <label for="pBearerToken" class="form-label">Bearer Token</label>
                        <textarea name="bearer_token" id="pBearerToken" rows="4" class="form-control">{{ old('bearer_token') }}</textarea>
                        <p class="text-muted small">Service account bearer tokens are perfectly valid to use outside the cluster and can be used to create identities for long standing jobs that wish to talk to the Kubernetes API.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Insecure</label>
                        <div>
                            <div class="radio radio-success radio-inline">
                                <input type="radio" id="pInsecureFalse" value="0" name="insecure" checked>
                                <label for="pInsecureFalse"> False</label>
                            </div>
                            <div class="radio radio-danger radio-inline">
                                <input type="radio" id="pInsecureTrue" value="1" name="insecure">
                                <label for="pInsecureTrue"> True</label>
                            </div>
                        </div>
                        <p class="text-muted small">Server should be accessed without verifying the TLS certificate. For testing only.</p>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="pServiceType" class="form-label">Service Type</label>
                            <select name="service_type" id="pServiceType">
                                <option value="nodeport" selected>NodePort</option>
                                <option value="loadbalancer">LoadBalancer</option>
                            </select>
                            <p class="text-muted small">ServiceTypes allow you to specify what kind of Service you want.</p>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="pStorageClass" class="form-label">Storage Class</label>
                            <input type="text" name="storage_class" id="pStorageClass" class="form-control" value="manual"/>
                            <p class="text-muted small">StorageClass provides a way for administrators to describe the "classes" of storage they offer.</p>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="pNamespace" class="form-label">Namespace</label>
                            <input type="text" name="ns" data-multiplicator="true" class="form-control" id="pNamespace" value="default"/>
                            <p class="text-muted small">Namespaces provides a mechanism for isolating groups of resources within a single cluster.</p>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-success pull-right">Create Cluster</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#pLocationId').select2();
        $('#pServiceType').select2();
    </script>
@endsection
