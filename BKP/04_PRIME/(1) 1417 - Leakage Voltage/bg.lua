local nightmare = false;

if GAMESTATE:IsPlayerEnabled(PLAYER_1) then
	local steps = GAMESTATE:GetCurrentSteps(PLAYER_1);
	if steps:GetStepsType() == 'StepsType_Pump_Single' and steps:GetDifficulty() == 'Difficulty_Challenge' then
		nightmare = true;
	end;
elseif GAMESTATE:IsPlayerEnabled(PLAYER_2) then
	local steps = GAMESTATE:GetCurrentSteps(PLAYER_2);
	if steps:GetStepsType() == 'StepsType_Pump_Single' and steps:GetDifficulty() == 'Difficulty_Challenge' then
		nightmare = true;
	end;
end;

if nightmare then
	return LoadActor("another.png")..{
		OnCommand=cmd();
	}
end;


return LoadActor("1417-wide.png")..{
	OnCommand=cmd();
}

	