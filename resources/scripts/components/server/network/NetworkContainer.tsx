import React, { useEffect, useState } from 'react';
import Spinner from '@/components/elements/Spinner';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { ServerContext } from '@/state/server';
import AllocationRow from '@/components/server/network/AllocationRow';

const NetworkContainer = () => {
    const server = ServerContext.useStoreState((state) => state.server.data!);
    const additional_ports = ServerContext.useStoreState((state) => state.server.data!.additional_ports);

    return (
        <ServerContentBlock showFlashKey={'server:network'} title={'Network'}>
            {!additional_ports ? (
                <Spinner size={'large'} centered />
            ) : (
                <>
                    <AllocationRow isDefault={true} port={String(server.default_port)} />

                    {additional_ports.map((port) => (
                        <AllocationRow isDefault={false} port={port} />
                    ))}
                </>
            )}
        </ServerContentBlock>
    );
};

export default NetworkContainer;
