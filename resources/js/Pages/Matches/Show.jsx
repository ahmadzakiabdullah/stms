import React, { useEffect, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

export default function MatchShow({ match: initialMatch, auth }) {
    const [match, setMatch] = useState(initialMatch);

    useEffect(() => {
        if (window.Echo) {
            window.Echo.channel(`matches.${match.id}`)
                .listen('.score.updated', (e) => {
                    setMatch(e.fixture);
                });
        }

        return () => {
            if (window.Echo) {
                window.Echo.leaveChannel(`matches.${match.id}`);
            }
        };
    }, [match.id]);

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Live Match: {match.match_number}</h2>}>
            <Head title={`Match ${match.match_number}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-center text-3xl flex justify-between items-center">
                                <div>
                                    <div className="text-sm text-gray-500 font-normal">Home</div>
                                    <div>{match.competitor1?.participant?.name || 'TBD'}</div>
                                    <div className="text-5xl font-bold mt-2">
                                        {match.result?.competitor_1_score ?? '-'}
                                    </div>
                                </div>

                                <div className="text-gray-400 mx-4">VS</div>

                                <div className="text-right">
                                    <div className="text-sm text-gray-500 font-normal">Away</div>
                                    <div>{match.competitor2?.participant?.name || 'TBD'}</div>
                                    <div className="text-5xl font-bold mt-2">
                                        {match.result?.competitor_2_score ?? '-'}
                                    </div>
                                </div>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-center text-gray-500 space-y-2">
                            <div>Event: {match.event?.name}</div>
                            <div>Status: <Badge>{match.status}</Badge></div>
                        </CardContent>
                    </Card>

                    <div className="mt-6 text-center text-sm text-gray-500 flex items-center justify-center space-x-2">
                        <span className="relative flex h-3 w-3">
                          <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span className="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                        </span>
                        <span>Live updates active</span>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
