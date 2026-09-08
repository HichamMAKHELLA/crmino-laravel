<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BarChart3, Bell, Building2, Gauge, LayoutGrid, ListChecks, Megaphone, Package, ScrollText, ShieldCheck, Target, Upload, Users } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import NotificationCenter from '@/components/NotificationCenter.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Prospection',
        href: '/leads',
        icon: Users,
    },
    {
        title: 'Sociétés',
        href: '/societes',
        icon: Building2,
    },
    {
        title: 'Pipeline',
        href: '/opportunites',
        icon: Target,
    },
    {
        title: 'Tâches',
        href: '/taches',
        icon: ListChecks,
    },
    {
        title: 'Campagnes',
        href: '/campagnes',
        icon: Megaphone,
    },
    {
        title: 'Catalogue',
        href: '/catalogue',
        icon: Package,
    },
    {
        title: 'Alertes',
        href: '/alertes',
        icon: Bell,
    },
    {
        title: 'Rapports',
        href: '/rapports',
        icon: BarChart3,
    },
    {
        title: 'Import',
        href: '/import',
        icon: Upload,
    },
    {
        title: 'Critères de score',
        href: '/criteres-score',
        icon: Gauge,
    },
    {
        title: 'Journal',
        href: '/journal',
        icon: ScrollText,
    },
    {
        title: 'Données personnelles',
        href: '/donnees-personnelles',
        icon: ShieldCheck,
    },
];

</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NotificationCenter />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
