<?php

namespace Group\Events;

final class KernalEvent extends \Event
{   
    const INIT = "kernal.init";

    const RESPONSE = "kernal.response";

    const REQUEST = "kernal.request";

    const EXCEPTION = "kernal.exception";

    const NOTFOUND = "kernal.notfound";

    const HTTPFINISH = "kernal.httpfinish";

    const SERVICE_CALL = "kernal.service_call";

    const SERVICE_FAIL = "kernal.service_fail";

    const SERVICE_ERROR = "kernal.service_error";
}
